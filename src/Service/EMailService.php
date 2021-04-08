<?php

namespace App\Service;

use App\Helper\EMailRecipient;
use App\Entity\EmailSending;
use App\Entity\EMailTemplate;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Messenger\MailingGroupNotification;
use App\Repository\EMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
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
        self::DESIGN_STANDARD => '/email/design/standard.html.twig',
    ];

    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private EntityManagerInterface $em;
    private Address $senderAddress;
    private EMailRepository $templateRepository;
    private Environment $twig;
    private IdmRepository $userRepository;
    private GroupService $groupService;
    private TextBlockService $textBlockService;
    private MessageBusInterface $messageBus;

    public function __construct(MailerInterface $mailer,
                                LoggerInterface $logger,
                                EntityManagerInterface $em,
                                GroupService $groupService,
                                TextBlockService $textBlockService,
                                EMailRepository $templateRepository,
                                Environment $twig,
                                MessageBusInterface $messageBus,
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
        $this->messageBus = $messageBus;
        //repos
        $this->userRepository = $manager->getRepository(User::class);
        $this->templateRepository = $templateRepository;
    }

    public static function recipientFromUser(User $user): EMailRecipient
    {
        return new EMailRecipient($user);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendByApplicationHook(string $hook, EMailRecipient $recipient, bool $throw = false): bool
    {
        if (!array_key_exists($hook, self::HOOKS)) {
            $this->logger->critical("Invalid Application hook supplied, no email sent.");
            return false;
        }
        $email = $this->generateEmailFromHook($hook, $recipient);
        return $this->sendEMail($email, $throw);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendByTemplate(EMailTemplate $template, EMailRecipient $recipient, bool $throw = false): bool
    {
        $email = $this->generateEmailFromTemplate($template, $recipient);
        return $this->sendEMail($email, $throw);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendEmail(Email $email, bool $throw): bool
    {
        try {
            $this->mailer->send($email);
            return true;
        } catch (HandlerFailedException | TransportExceptionInterface $e) {
            if ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious();
            }
            if ($throw) {
                throw $e;
            }
            $this->logger->error("Error sending email: {$e->getMessage()}", ['exception' => $e]);
            return false;
        }
    }

    private function generateEmailFromHook(string $hook, EMailRecipient $recipient): ?Email
    {
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
                'token' => self::generateToken($recipient->getUuid(), $config[self::HOOK_TOKEN]),
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

    private function generateEmailFromTemplate(EMailTemplate $template, EMailRecipient $recipient): ?Email
    {
        if (empty($recipient) || empty($recipient->getEmailAddress())) {
            $this->logger->error('No email address given');
            return null;
        }
        $email = $this->renderTemplate($template, $recipient);
        return (new Email())
            ->from($this->senderAddress)
            ->to($recipient->getAddressObject())
            ->subject($email['subject'])
            ->html($email['html'])
            ->text($email['text']);
    }

    private array $template_cache = [];

    public function renderTemplate(EMailTemplate $template, EMailRecipient $recipient): array
    {
        $key = hash('sha256', serialize($template));
        $body = $template->getBody();
        $subject = $template->getSubject();
        $html = $this->template_cache[$key]
            ?? ($this->template_cache[$key] = $this->twig->render($this->getDesignFile($template), [
                'subject' => $subject,
                'body' => $body
            ]));

        $subject = $this->replaceVariableTokens($subject, $recipient);
        $html = $this->replaceVariableTokens($html, $recipient);
        $text = strip_tags($html);

        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }

    private function getDesignFile(EMailTemplate $template): string
    {
        return self::NEWSLETTER_DESIGNS[$template->getDesignFile()] ?? self::NEWSLETTER_DESIGNS[self::DESIGN_STANDARD];
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
                    throw new Exception('MailData is not valid: '.implode(',', $errors));
                }
                $text = str_replace($match, $filled, $text);
            }
        }

        return $text;
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
        $this->messageBus->dispatch(new MailingGroupNotification($sending->getId()), [
            new DelayStamp(10000)
        ]);
        return true;
    }

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
