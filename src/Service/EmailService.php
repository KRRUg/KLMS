<?php

namespace App\Service;

use App\Entity\Email;
use App\Entity\EmailSending;
use App\Helper\EmailRecipient;
use App\Messenger\MailingGroupNotification;
use App\Messenger\MailingHookNotification;
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
use Symfony\Component\Mime;
use Twig\Environment;

class EmailService
{
    final public const APP_HOOK_REGISTRATION_CONFIRM = 'REGISTRATION_CONFIRMATION';
    final public const APP_HOOK_RESET_PW = 'PASSWORD_RESET';
    final public const APP_HOOK_CHANGE_NOTIFICATION = 'CHANGE_NOTIFICATION';
    final public const APP_HOOK_ORDER = 'ORDER';

    final public const HOOK_TEMPLATE = 'template';
    final public const HOOK_SUBJECT = 'subject';
    final public const HOOK_SUBJECT_DEFAULT = 'subject_default';
    final public const HOOK_CONTEXT = 'context';

    final public const HOOKS = [
        self::APP_HOOK_REGISTRATION_CONFIRM => [
            self::HOOK_SUBJECT => 'email.register.subject',
            self::HOOK_SUBJECT_DEFAULT => 'Registrierung',
            self::HOOK_TEMPLATE => '/email/hooks/registration.html.twig',
            self::HOOK_CONTEXT => ['user', 'token'],
        ],
        self::APP_HOOK_RESET_PW => [
            self::HOOK_SUBJECT => 'email.reset.subject',
            self::HOOK_SUBJECT_DEFAULT => 'Passwort zurücksetzen',
            self::HOOK_TEMPLATE => '/email/hooks/reset.html.twig',
            self::HOOK_CONTEXT => ['user', 'token'],
        ],
        self::APP_HOOK_CHANGE_NOTIFICATION => [
            self::HOOK_SUBJECT => 'email.notify.subject',
            self::HOOK_SUBJECT_DEFAULT => 'Hinweis',
            self::HOOK_TEMPLATE => '/email/hooks/change.html.twig',
            self::HOOK_CONTEXT => ['message'],
        ],
        self::APP_HOOK_ORDER => [
            self::HOOK_SUBJECT => 'email.shop.subject',
            self::HOOK_SUBJECT_DEFAULT => 'LAN-Shop',
            self::HOOK_TEMPLATE => '/email/hooks/shop.html.twig',
            self::HOOK_CONTEXT => ['order', 'showPaymentInfo', 'showPaymentSuccess'],
        ]
    ];

    final public const DESIGN_STANDARD = 'Standard';
    final public const NEWSLETTER_DESIGNS = [
        self::DESIGN_STANDARD => '/email/design/standard.html.twig',
    ];

    private readonly LoggerInterface $logger;
    private readonly MailerInterface $mailer;
    private readonly EntityManagerInterface $em;
    private readonly Mime\Address $senderAddress;
    private readonly Environment $twig;
    private readonly SettingService $settingService;
    private readonly MessageBusInterface $messageBus;
    private readonly string $appSecret;

    public function __construct(MailerInterface $mailer,
                                LoggerInterface $logger,
                                EntityManagerInterface $em,
                                SettingService $settingService,
                                Environment $twig,
                                MessageBusInterface $messageBus,
                                string $appSecret)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->settingService = $settingService;
        $this->em = $em;
        $this->twig = $twig;
        $this->appSecret = $appSecret;
        $mailAddress = $_ENV['MAILER_DEFAULT_SENDER_EMAIL'];
        $mailName = $_ENV['MAILER_DEFAULT_SENDER_NAME'];
        $this->senderAddress = new Mime\Address($mailAddress, $mailName);
        $this->messageBus = $messageBus;
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
            new DelayStamp(intval($_ENV['MAILER_NEWSLETTER_SEND_WAITTIME'] ?? 60) * 1000),
        ]);

        return true;
    }

    public function scheduleHook(string $hook, EmailRecipient $recipient, array $context): bool
    {
        if (!array_key_exists($hook, self::HOOKS)) {
            $this->logger->critical('Invalid Application hook supplied, no email sent.');
            return false;
        }
        $config = self::HOOKS[$hook];
        if (!empty(array_diff($config[self::HOOK_CONTEXT], array_keys($context)))) {
            $this->logger->critical('Invalid Application hook context supplied, no email sent.');
            return false;
        }
        $this->messageBus->dispatch(new MailingHookNotification($hook, $recipient, $context));

        return true;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendByApplicationHook(string $hook, EmailRecipient $recipient, array $context = [], bool $throw = false): bool
    {
        $email = $this->generateEmailFromHook($hook, $recipient, $context);

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
        } catch (HandlerFailedException|TransportExceptionInterface $e) {
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

    private function generateEmailFromHook(string $hook, EmailRecipient $recipient, array $context = []): ?Mime\Email
    {
        if (empty($recipient->getEmailAddress())) {
            $this->logger->error('No email address given or user object was empty');
            return null;
        }
        $config = self::HOOKS[$hook] ?? null;
        if (empty($config)) {
            $this->logger->error('Invalid Hook configuration');
            return null;
        }
        $subject = $this->settingService->get($config[self::HOOK_SUBJECT]);
        $subject = empty($subject) ? $config[self::HOOK_SUBJECT_DEFAULT] : $subject;

        return (new TemplatedEmail())
            ->from($this->senderAddress)
            ->to($recipient->getAddressObject())
            ->subject($subject)
            ->htmlTemplate($config[self::HOOK_TEMPLATE])
            ->context($context);
    }

    private function generateUnsubscribeToken(UuidInterface $uuid): string
    {
        $uuid = str_replace('-', '', $uuid->toString());
        $token = 'un'.$uuid.'subscribe';
        $token = hash_hmac('sha256', $token, $this->appSecret);

        return $uuid.$token;
    }

    public function handleUnsubscribeToken(string $token): ?UuidInterface
    {
        $regex = '/^([0-9a-f]{32})([0-9a-f]{64})$/is';
        if (preg_match($regex, $token, $matches) === 1) {
            $uuid = Uuid::fromString($matches[1]);

            return ($this->generateUnsubscribeToken($uuid) === $matches[0]) ? $uuid : null;
        }

        return null;
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
                'unsubscribe' => 'UNSUBSCRIBE_URL_TOKEN_PLACEHOLDER',
            ]));

        $subject = $this->replaceVariables($subject, $recipient->getDataArray());
        $html = $this->replaceVariables($html, $recipient->getDataArray());
        $html = $this->replaceVariables($html, [
            'UNSUBSCRIBE_URL_TOKEN_PLACEHOLDER' => $this->generateUnsubscribeToken($recipient->getUuid()),
        ], false);
        $text = strip_tags($html);

        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }

    private function replaceVariables(string $text, array $replacements, bool $escapeSign = true): string
    {
        foreach ($replacements as $key => $value) {
            $text = preg_replace($escapeSign ? '/{{2}'.$key.'}{2}/' : '/'.$key.'/', trim((string) $value), $text) ?? $text;
        }

        return $text;
    }

    private function getDesignFile(Email $template): string
    {
        return self::NEWSLETTER_DESIGNS[$template->getDesignFile()] ?? self::NEWSLETTER_DESIGNS[self::DESIGN_STANDARD];
    }

    public function deleteTemplate(Email $template): bool
    {
        if ($template->wasSent()) {
            return false;
        }
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
