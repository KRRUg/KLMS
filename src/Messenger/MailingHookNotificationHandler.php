<?php

namespace App\Messenger;

use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

class MailingHookNotificationHandler
{
    private readonly LoggerInterface $logger;
    private readonly EmailService $mailService;

    public function __construct(EmailService $mailService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->mailService = $mailService;
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[AsMessageHandler]
    public function __invoke(MailingHookNotification $mailingHookNotification)
    {
        $this->mailService->sendByApplicationHook(
            $mailingHookNotification->getHook(),
            $mailingHookNotification->getRecipient(),
            $mailingHookNotification->getContext(), true);
    }
}
