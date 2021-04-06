<?php

namespace App\Service;

use App\Helper\EMailRecipient;
use App\Entity\EmailSending;
use App\Entity\EMailTemplate;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\EmailSendingRepository;
use App\Repository\EMailTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

//TODO: Exceptions abfangen
class EMailService
{
    const APP_HOOK_REGISTRATION_CONFIRM = 'REGISTRATION_CONFIRMATION';

    const HOOK_TEMPLATE = 'template';
    const HOOK_SUBJECT = 'subject';
    const HOOK_TOKEN = 'token';

    // TODO call this hook in the registration process and disallow login of users without confirmed email address
    const HOOKS = [
        self::APP_HOOK_REGISTRATION_CONFIRM => [
            self::HOOK_SUBJECT => "register.subject",
            self::HOOK_TEMPLATE => '/email/registration.html.twig',
            self::HOOK_TOKEN => 'register'
        ],
    ];

    const DESIGN_STANDARD = 'Standard';

    const NEWSLETTER_DESIGNS = [
        self::DESIGN_STANDARD => '/email/standard.html.twig',
    ];

    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private EntityManagerInterface $em;
    private Address $senderAddress;
    private EMailTemplateRepository $templateRepository;
    private Environment $twig;
    private IdmRepository $userRepository;
    private GroupService $groupService;
    private TextBlockService $textBlockService;

    public function __construct(MailerInterface $mailer,
                                LoggerInterface $logger,
                                EntityManagerInterface $em,
                                GroupService $groupService,
                                TextBlockService $textBlockService,
                                EMailTemplateRepository $templateRepository,
                                Environment $twig,
                                IdmManager $manager)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->groupService = $groupService;
        $this->textBlockService = $textBlockService;
        $this->em = $em;
        $this->twig = $twig;
        $mailAddress = $_ENV['MAILER_DEFAULT_SENDER_EMAIL'];
        $mailName = $_ENV['MAILER_DEFAULT_SENDER_NAME'];
        $this->senderAddress = new Address($mailAddress, $mailName);
        //repos
        $this->userRepository = $manager->getRepository(User::class);
        $this->templateRepository = $templateRepository;
    }

    // TODO return boolean to indicate whether if email was sent successfully
    public function sendByApplicationHook(string $hook, User $user)
    {
        if (!array_key_exists($hook, self::HOOKS)) {
            $this->logger->critical("Invalid Application hook supplied, no email sent.");
            return;
        }
        $email = $this->generateEmailFromHook($hook, $user);
        $this->sendEMail($email);
    }

    // TODO return boolean to indicate whether if email was sent successfully
    public function sendByTemplate(EMailTemplate $template, User $user)
    {
        $email = $this->generateEmailFromTemplate($template, $user);
        $this->sendEMail($email);
    }

    private function sendEmail(Email $email)
    {
        try {
            $this->mailer->send($email);
        } catch (Exception | TransportExceptionInterface $e) {
            // TODO plan error handling and check how async error handling is done
            $this->logger->error($e);
        }
    }

    private function generateEmailFromHook(string $hook, User $user): ?Email
    {
        $recipient = new EMailRecipient($user);
        if (empty($recipient) || empty($recipient->getEmailAddress())) {
            $this->logger->error('No email address given or user object was null');
            return null;
        }
        $config = self::HOOKS[$hook];
        if (!$this->textBlockService->validKey($config[self::HOOK_SUBJECT])) {
            $this->logger->error('Invalid Hook configuration');
            return null;
        }
        return (new TemplatedEmail())
            ->from($this->senderAddress)
            ->to($recipient->getAddressObject())
            ->subject($this->textBlockService->get($config[self::HOOK_SUBJECT]))
            ->htmlTemplate($config[self::HOOK_TEMPLATE])
            ->context([
                'token' => self::generateToken($user->getUuid(), $config[self::HOOK_TOKEN]),
            ]);
    }

    private static function generateToken(UuidInterface $uuid, string $method): string
    {
        $uuid = str_replace('-', '', $uuid->toString());
        $token = $uuid . $method . $_ENV['APP_SECRET'];
        $token = hash('sha256', $token);
        return $uuid . $token;
    }

    public static function handleToken(string $token, UuidInterface &$uuid): string
    {
        $regex = '/^([0-9a-f]{32})([0-9a-f]{64})$/is';
        $matches = [];
        if (preg_match($regex, $token, $matches) === 1) {
            $uuid = Uuid::fromString($matches[1]);
            foreach (self::HOOKS as $hook => $config) {
                $t = $config[self::HOOK_TOKEN] ?? "";
                if (empty($t))
                    continue;
                if (self::generateToken($uuid, $t) == $matches[0])
                    return $t;
            }
        }
        return '';
    }

    private function generateEmailFromTemplate(EMailTemplate $template, User $user, array $payload = null): ?Email
    {
        $recipient = new EMailRecipient($user);
        if (empty($recipient) || empty($recipient->getEmailAddress())) {
            $this->logger->error('No email address given or user object was null');
            return null;
        }
        $template = $this->renderTemplate($template, $user, $payload);
        // TODO Lösung für Text-only finden
        return (new Email())->from($this->senderAddress)
            ->to($recipient->getAddressObject())
            ->subject($template->getSubject())
            ->text(strip_tags($template->getBody()))
            ->html($template->getBody());
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

        //TODO twig render eventuell mit email render tauschen; eher net
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
        if (null == $design || empty($design)) {
            $design = 'TEXT';
        }
        return $design;
    }

    public function createSending(EMailTemplate $template): bool
    {
        if (!GroupService::groupExists($template->getRecipientGroup())) {
            $this->logger->warning("Can't create sending for invalid group");
            return false;
        }

        $sending = new EmailSending();
        $sending->setTemplate($template);

        $this->em->persist($sending);
        $this->em->flush();
        return true;
    }

//    public function createSendingTasks(EmailSending $sending): int
//    {
//        //User holen
//        $generatedCount = 0;
//        $errorCount = 0;
//        $users = $this->getPossibleEmailRecipients($sending->getRecipientGroup());
//        $template = $sending->getTemplate();
//        $sending->setStatus('Empfänger werden geladen');
//        $sending->setIsInSending(true);
//        $sending->setRecipientCount($users->count());
//        $this->em->persist($sending);
//        $this->em->flush();
//
//        foreach ($users as $user) {
//            if (null == $this->sendEMail($template, $user)) {
//                ++$generatedCount;
//            } else {
//                ++$errorCount;
//            }
//        }
//        $sending->setRecipientCountGenerated($generatedCount);
//        $sending->setErrorCount($errorCount);
//        $this->em->persist($sending);
//        $this->em->flush();
//
//        return $generatedCount;
//    }

    public function deleteTemplate(EMailTemplate $template): bool
    {
        if ($template->wasSent())
            return false;
        $this->em->remove($template);
        $this->em->flush();
        return true;
    }

    public function cancelSending(EMailTemplate $email): bool
    {
        $this->em->beginTransaction();
        $sending = $email->getEmailSending();
        if (!$sending->isNotStarted()) {
            $this->em->rollback();
            return false;
        }
        $email->setEmailSending(null);
        $this->em->flush();
        $this->em->commit();
        return true;
    }
}
