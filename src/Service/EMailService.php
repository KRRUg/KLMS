<?php

namespace App\Service;

use App\Entity\EMail\EMailRecipient;
use App\Entity\EMail\EmailSending;
use App\Entity\EMail\EMailTemplate;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\EMail\EmailSendingRepository;
use App\Repository\EMail\EMailTemplateRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

//TODO: Exceptions abfangen
class EMailService
{
    const APPLICATIONHOOKS = [
        '-' => null,
        'REGISTRATION_CONFIRMATION' => '/email/REGISTRATION_CONFIRMATION.html.twig',
        'ADMIN_ERRORMAIL' => '/email/ADMIN_ERRORMAIL.html.twig',
        'ADMIN_FOO' => '/email/ADMIN_FOO.html.twig',
        'ADMIN_BAR' => '/email/ADMIN_BAR.html.twig',
    ];
    const NEWSLETTER_DESIGNS = [
        'STANDARD' => '/email/STANDARD.html.twig',
    ];

    protected LoggerInterface $logger;
    protected EntityManagerInterface $em;
    protected MailerInterface $mailer;
    protected Address $senderAddress;
    protected EMailTemplateRepository $templateRepository;
    protected EmailSendingRepository $sendingRepository;
    protected Environment $twig;
    protected User $systemMessageUser;
    protected IdmRepository $userRepository;

    public function __construct(MailerInterface $mailer,
                                LoggerInterface $logger,
                                EMailTemplateRepository $templateRepository,
                                EmailSendingRepository $sendingRepository,
                                EntityManagerInterface $em,
                                Environment $twig,
                                IdmManager $manager)
    {
        $this->mailer = $mailer;
        $this->userRepository = $manager->getRepository(User::class);
        $mailAddress = $_ENV['MAILER_DEFAULT_SENDER_EMAIL'];
        $mailName = $_ENV['MAILER_DEFAULT_SENDER_NAME'];
        $this->senderAddress = new Address($mailAddress, $mailName);
        $this->logger = $logger;
        $this->em = $em;
        //repos
        $this->templateRepository = $templateRepository;
        $this->sendingRepository = $sendingRepository;
        $this->twig = $twig;
        // TODO remove IDM access from constructor
        //$this->systemMessageUser = $this->userRepository->findOneById($_ENV['MAILER_SYSTEM_MESSAGE_USER_GUID']);
    }

    public function sendByApplicationHook(string $applicationHook, User $user, string $processStepName = null, array $payload = null)
    {
        //prüfen ob gültiger Applicationhook übergeben wurde
        if (in_array($applicationHook, self::APPLICATIONHOOKS)) {
            $hook = $applicationHook;
            //Template dazu suchen
            $template = $this->templateRepository->findOneBy(['applicationHook' => $hook]);
            if (null !== $template) {
                $this->sendEMail($template, $user, $payload);
            } else {
                //payload für Fehlermeldung generieren

                //TODO Errorhandling fertigmachen
                $payload = $payload ?? [];
                $payload['ERROR_MESSAGE'] = "E-Mail Template für ApplicationHook $hook nicht gefunden";
                $payload['USER_DETAILS'] = $user->getUuid().' => '.$user->getNickname().' => '.$user->getEmail();
                if (null !== $processStepName) {
                    $payload['STEP'] = "Fehler-Step : $processStepName";
                }
                $this->logger->critical('Fehler in Mailversand: '.json_encode($payload));
                if ('TemplateNotFoundMessage' == $processStepName) {
                    $errorTemplate = new  EMailTemplate();
                    $errorTemplate->setSubject('Template für ErrorMail nicht gefunden');
                    $errorTemplate->setBody("Template für $processStepName nicht gefunden");
                    $this->sendEMail($errorTemplate, $this->systemMessageUser, $payload);
                } else {
                    $this->sendByApplicationHook(self::APPLICATIONHOOKS['ADMIN_ERRORMAIL'], $this->systemMessageUser, 'TemplateNotFoundMessage', $payload);
                }
            }
        }
    }

    private function sendEMail(EMailTemplate $template, User $user, array $payload = null): ?string
    {
        $recipient = new EMailRecipient($user);
        $error = null;

        if (empty($recipient) || empty($recipient->getEmailAddress())) {
            $error = 'No email address was given or user object was null';
        }
        if (null != $error) {
            return $error;
        }

        try {
            $template = $this->renderTemplate($template, $user, $payload);
            $email = (new Email())->from($this->senderAddress)
                ->to($recipient->getAddressObject())
                ->subject($template->getSubject())
                ->text(strip_tags($template->getBody())) // TODO Lösung für Text-only finden
                ->html($template->getBody());
            $this->mailer->send($email);
        } catch (Exception | TransportExceptionInterface $e) {
            $this->logger->error($e);
            $error = $e;
        } finally {
            return $error;
        }
    }

    public function renderTemplate(EMailTemplate $template, User $user, array $payload = null): EMailTemplate
    {
        $recipient = new EMailRecipient($user);
        $text = $template->getBody();
        $subject = $template->getSubject();

        $text = $this->replaceVariableTokens($text, $recipient);
        $subject = $this->replaceVariableTokens($subject, $recipient);

        //template auf E-Mail clonen, damit es verändert werden kann
        $email = clone $template;
        $email->setBody($text);
        $email->setSubject($subject);

        //TODO twig render eventuell mit email render tauschen
        $designFile = $this->getDesignFile($email);
        if ('TEXT' == $designFile) {
            $html = $text;
        } else {
            $html = $this->twig->render($designFile, ['template' => $email, 'user' => $user, 'payload' => $payload]);
        }
        $email->setBody($html);

        return $email;
    }

    private function replaceVariableTokens($text, EMailRecipient $mailRecipient)
    {
        $errors = [];
        $recipientData = $mailRecipient->getDataArray();
        preg_match_all('/({{2}([^}]+)}{2})/', $text, $matches);
        if (isset($matches[0]) && count($matches[0])) {
            foreach ($matches[0] as $match) {
                $variableName = str_replace('{', '', $match);
                $variableName = str_replace('}', '', $variableName);
                $variableName = trim(strtolower($variableName));
                $filled = $recipientData[$variableName];
                if (empty($filled)) {
                    $error = "Variable $variableName not valid: ".sprintf($mailRecipient);
                    $this->logger->error($error);
                    array_push($errors, $error);
                }
                if (count($errors) > 0) {
                    throw  new  Exception('MailData is not valid: '.implode(',', $errors));
                }
                $text = str_replace($match, $filled, $text);
            }
        }

        return $text;
    }

    private function getDesignFile(EMailTemplate $template): string
    {
        $design = $template->getDesignFile();
        if ($template->isApplicationHooked()) {
            $design = $template->getApplicationHook();
        }
        if (null == $design || empty($design)) {
            $design = 'TEXT';
        }

        return $design;
    }

    public function createSending(EMailTemplate $template, string $userGroupName = null)
    {
        $userGroup = array_search($userGroupName, $this->getEmailRecipientGroups());
        //Wenn keine Usergruppe mit diesem wert gefunden wurde, dann erste nehmen
        $userGroup = false === $userGroup ? array_values($this->getEmailRecipientGroups())[0] : $userGroup;

        $sending = new  EmailSending();
        $sending->setEMailTemplate(clone $template)
            ->setRecipientGroup($userGroup)
            ->setStatus('Sendung erstellt');

        $this->em->persist($sending);
        $this->em->flush();
    }

    public function getEmailRecipientGroups()
    {
        return [
            'TEST0' => '00000000-0000-0000-0000-000000000000',
            'TEST1' => '00000000-0000-0000-0000-000000000000',
            'TEST2' => '00000000-0000-0000-0000-000000000000',
            'TEST3' => '00000000-0000-0000-0000-000000000000',
            'TEST4' => '00000000-0000-0000-0000-000000000000',
            'TEST5' => '00000000-0000-0000-0000-000000000000',
            'TEST6' => '00000000-0000-0000-0000-000000000000',
        ];
    }

    public function createSendingTasksAllSendings(SymfonyStyle $io = null)
    {
        $sendings = $this->sendingRepository->findNewsletterSendable();
        if (0 == count($sendings)) {
            $io->note('No sendable newsletters found');
        }
        foreach ($sendings as $sending) {
            if ($sending->getStartTime() <= new  DateTime()) {
                $sendingName = $sending->getEMailTemplate()->getName().' for UserGroup '.$sending->getRecipientGroup();
                $io->note("Starting: $sendingName");
                $countGenerated = $this->createSendingTasks($sending);
                $io->note("Finished: $sendingName $countGenerated Mails generated");
                $sending->setStatus('E-Mail Jobs generiert, beginne mit Versendung.');
                $this->em->persist($sending);
                $this->em->flush();
            }
        }
    }

    public function createSendingTasks(EmailSending $sending): int
    {
        //User holen
        $generatedCount = 0;
        $errorCount = 0;
        $users = $this->getPossibleEmailRecipients($sending->getRecipientGroup());
        $template = $sending->getEMailTemplate();
        $sending->setStatus('Empfänger werden geladen');
        $sending->setIsInSending(true);
        $sending->setRecipientCount($users->count());
        $this->em->persist($sending);
        $this->em->flush();

        foreach ($users as $user) {
            if (null == $this->sendEMail($template, $user)) {
                ++$generatedCount;
            } else {
                ++$errorCount;
            }
        }
        $sending->setRecipientCountGenerated($generatedCount);
        $sending->setErrorCount($errorCount);
        $this->em->persist($sending);
        $this->em->flush();

        return $generatedCount;
    }

    private function getPossibleEmailRecipients($group = null): ArrayCollection
    {
        //TODO: Mockdaten gegen echte Daten austauschen
        //$usersToFind = hatasSuperMethodeZumAbholenDerUserAusDenUsergruppen();
        $usersToFind = [
            '00000000-0000-0000-0000-000000000000',
            '00000000-0000-0000-0000-000000000001',
            '00000000-0000-0000-0000-000000000002',
            '00000000-0000-0000-0000-000000000003',
            '00000000-0000-0000-0000-000000000004',
        ];
        $users = $this->userRepository->findById($usersToFind);

        return new ArrayCollection($users);
    }

    public function sendSingleEmail(EMailTemplate $template, User $user)
    {
        $this->sendEMail($template, $user);
    }

    public function deleteTemplate(EMailTemplate $template)
    {
        if ($template->isDeletable()) {
            $this->em->remove($template);
            $this->em->flush();
        }
    }

    public function deleteSending(EmailSending $sending)
    {
        if ($sending->getIsDeletable()) {
            $this->em->remove($sending);
            $this->em->flush();
        }
    }

    private function repairApplicationHookTemplates()
    {
        $hooks = EMailService::APPLICATIONHOOKS;
        foreach ($hooks as $hook) {
            if (null == $this->templateRepository->findBy(['applicationHook' => $hook])) {
                $template = new EMailTemplate();
                $template->setApplicationHook($hook);
                $template->setName($hook);
                $template->setSubject($hook);

                $this->em->persist($template);
            }
        }
        $this->em->flush();
    }
}
