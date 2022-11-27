<?php

namespace App\Messenger;

use App\Entity\EmailSending;
use App\Entity\EmailSendingItem;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class MailingNotificationHandler implements MessageSubscriberInterface
{
    private readonly LoggerInterface $logger;
    private readonly EntityManagerInterface $em;
    private readonly EmailService $mailService;
    private readonly ObjectRepository $sendingRepo;
    private readonly ObjectRepository $sendingItemRepo;

    public function __construct(EmailService $mailService, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->em = $em;
        $this->sendingRepo = $this->em->getRepository(EmailSending::class);
        $this->sendingItemRepo = $this->em->getRepository(EmailSendingItem::class);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function handle(MailingNotification $mailingNotification)
    {
        $id = $mailingNotification->getSendingId();
        $recipient = $mailingNotification->getRecipient();
        $this->em->beginTransaction();
        $sending = $this->sendingRepo->findOneBy(['id' => $id]);
        if (empty($sending)) {
            $this->logger->notice("Cancel sending email of non-existing sending id {$id}");
            $this->em->rollback();

            return;
        }
        $sendingItem = $this->sendingItemRepo->findOneBy(['guid' => $recipient->getUuid(), 'sending' => $sending]);
        if (empty($sendingItem)) {
            $this->logger->notice("Cancel sending email of non-existing sending item (template id {$id})");
            $this->em->rollback();

            return;
        }
        if ($sendingItem->getSuccess()) {
            $this->logger->notice("Cancel sending already sent email (template id {$id})");
            $this->em->rollback();

            return;
        }
        $template = $sending->getTemplate();
        $this->logger->info("Sending template {$template->getName()} (id {$template->getId()}) to {$recipient->getEmailAddress()} ({$recipient->getUuid()->toString()})");
        $sendingItem->setTries($sendingItem->getTries() + 1);
        try {
            $this->mailService->sendByTemplate($template, $recipient, true);
            $sendingItem->setSuccess(true);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Cannot Send Email', ['exception' => $e]);
            $sendingItem->setSuccess(false);
            throw $e;
        } finally {
            $this->em->flush();
            $this->em->commit();
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function hook(MailingHookNotification $mailingHookNotification)
    {
        $this->mailService->sendByApplicationHook(
            $mailingHookNotification->getHook(),
            $mailingHookNotification->getRecipient(),
            $mailingHookNotification->getContext(), true);
    }

    public static function getHandledMessages(): iterable
    {
        yield MailingNotification::class => [
            'method' => 'handle',
        ];
        yield MailingHookNotification::class => [
            'method' => 'hook',
        ];
    }
}
