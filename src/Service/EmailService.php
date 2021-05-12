<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Email;
use App\Entity\EmailSending;
use App\Helper\EmailRecipient;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Messenger\MailingGroupNotification;
use App\Messenger\MailingHookNotification;
use App\Repository\EmailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Mime as Mime;
use Twig\Environment;

class EmailService
{
    const APP_HOOK_REGISTRATION_CONFIRM = 'REGISTRATION_CONFIRMATION';

    const HOOK_TEMPLATE = 'template';
    const HOOK_SUBJECT = 'subject';
    const HOOK_TOKEN = 'token';

    const HOOKS = [
        self::APP_HOOK_REGISTRATION_CONFIRM => [
            self::HOOK_SUBJECT => "email.register.subject",
            self::HOOK_TEMPLATE => '/email/hooks/registration.html.twig',
            self::HOOK_TOKEN => 'register'
        ],
    ];

    const UNSUBSCRIBE_TOKEN = 'unsubscribe';

    const DESIGN_STANDARD = 'Standard';
    const NEWSLETTER_DESIGNS = [
        self::DESIGN_STANDARD => '/email/design/standard.html.twig',
    ];

    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private EntityManagerInterface $em;
    private Mime\Address $senderAddress;
    private EmailRepository $templateRepository;
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
                                EmailRepository $templateRepository,
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
        $this->senderAddress = new Mime\Address($mailAddress, $mailName);
        $this->messageBus = $messageBus;
        //repos
        $this->userRepository = $manager->getRepository(User::class);
        $this->templateRepository = $templateRepository;
    }

    public function scheduleSending(Email $template): bool
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
            new DelayStamp(intval($_ENV['MAILER_NEWSLETTER_SEND_WAITTIME'] ?? 60) * 1000)
        ]);
        return true;
    }

    public function scheduleHook(string $hook, EmailRecipient $recipient): bool
    {
        if (!array_key_exists($hook, self::HOOKS)) {
            $this->logger->critical("Invalid Application hook supplied, no email sent.");
            return false;
        }
        $this->messageBus->dispatch(new MailingHookNotification($hook, $recipient));
        return true;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendByApplicationHook(string $hook, EmailRecipient $recipient, bool $throw = false): bool
    {
        $email = $this->generateEmailFromHook($hook, $recipient);
        return $this->sendEmail($email, $throw);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendByTemplate(Email $template, EmailRecipient $recipient, bool $throw = false): bool
    {
        $email = $this->generateEmailFromTemplate($template, $recipient);
        return $this->sendEmail($email, $throw);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendEmail(Mime\Email $email, bool $throw): bool
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

    private function generateEmailFromHook(string $hook, EmailRecipient $recipient): ?Mime\Email
    {
        if (empty($recipient) || empty($recipient->getEmailAddress())) {
            $this->logger->error('No email address given or user object was empty');
            return null;
        }
        $config = self::HOOKS[$hook] ?? null;
        if (empty($config) || !$this->textBlockService->validKey($config[self::HOOK_SUBJECT])) {
            $this->logger->error('Invalid Hook configuration');
            return null;
        }
        return (new TemplatedEmail())
            ->from($this->senderAddress)
            ->to($recipient->getAddressObject())
            ->subject($this->textBlockService->get($config[self::HOOK_SUBJECT]))
            ->htmlTemplate($config[self::HOOK_TEMPLATE])
            ->context([
                'token' => $this->generateToken($recipient->getUuid(), $config[self::HOOK_TOKEN]),
            ]);
    }

    private static function generateToken(UuidInterface $uuid, string $method): string
    {
        $uuid = str_replace('-', '', $uuid->toString());
        $token = $uuid . $method . $_ENV['APP_SECRET'];
        $token = hash('sha256', $token);
        return $uuid . $token;
    }

    public static function handleToken(string $token, ?UuidInterface &$uuid): string
    {
        $regex = '/^([0-9a-f]{32})([0-9a-f]{64})$/is';
        $tokens = [self::UNSUBSCRIBE_TOKEN];
        foreach (self::HOOKS as $hook => $config) {
            $t = $config[self::HOOK_TOKEN] ?? "";
            if (empty($t))
                continue;
            $tokens[] = $t;
        }
        if (preg_match($regex, $token, $matches) === 1) {
            $uuid = Uuid::fromString($matches[1]);
            foreach ($tokens as $t) {
                if (self::generateToken($uuid, $t) == $matches[0])
                    return $t;
            }
        }
        return '';
    }

    private function generateEmailFromTemplate(Email $template, EmailRecipient $recipient): ?Mime\Email
    {
        if (empty($recipient) || empty($recipient->getEmailAddress())) {
            $this->logger->error('No email address given');
            return null;
        }
        $email = $this->renderTemplate($template, $recipient);
        return (new Mime\Email())
            ->from($this->senderAddress)
            ->to($recipient->getAddressObject())
            ->subject($email['subject'])
            ->html($email['html'])
            ->text($email['text']);
    }

    private array $template_cache = [];

    public function renderTemplate(Email $template, EmailRecipient $recipient): array
    {
        $key = hash('sha256', serialize($template));
        $body = $template->getBody();
        $subject = $template->getSubject();
        $html = $this->template_cache[$key]
            ?? ($this->template_cache[$key] = $this->twig->render($this->getDesignFile($template), [
                'subject' => $subject,
                'body' => $body,
                'unsubscribe' => "UNSUBSCRIBE_URL_TOKEN_PLACEHOLDER",
            ]));

        $subject = $this->replaceVariables($subject, $recipient->getDataArray());
        $html = $this->replaceVariables($html, $recipient->getDataArray());
        $html = $this->replaceVariables($html, [
            "UNSUBSCRIBE_URL_TOKEN_PLACEHOLDER" => $this->generateToken($recipient->getUuid(), self::UNSUBSCRIBE_TOKEN)
        ], false);
        $text = strip_tags($html);

        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }

    private function replaceVariables(string $text, array $replacements, bool $escapeSign = true): string
    {
        foreach ($replacements as $key => $value) {
                $text = preg_replace($escapeSign ? '/{{2}'. $key . '}{2}/' : '/' . $key . '/', trim($value), $text) ?? $text;
        }
        return $text;
    }

    private function getDesignFile(Email $template): string
    {
        return self::NEWSLETTER_DESIGNS[$template->getDesignFile()] ?? self::NEWSLETTER_DESIGNS[self::DESIGN_STANDARD];
    }

    public function deleteTemplate(Email $template): bool
    {
        if ($template->wasSent())
            return false;
        $this->em->remove($template);
        $this->em->flush();
        return true;
    }

    public function cancelSending(Email $email): bool
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
