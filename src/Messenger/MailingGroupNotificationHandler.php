<?php

namespace App\Messenger;

use App\Entity\EmailSending;
use App\Entity\EmailSendingItem;
use App\Helper\EmailRecipient;
use App\Repository\EmailRepository;
use App\Service\EmailService;
use App\Service\GroupService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MailingGroupNotificationHandler implements MessageHandlerInterface
{
    private readonly EmailService $mailService;
    private readonly GroupService $groupService;
    private readonly ObjectRepository $sendingRepo;
    private readonly EntityManagerInterface $em;
    private readonly MessageBusInterface $bus;
    private readonly LoggerInterface $logger;

    public function __construct(EmailService $mailService,
                                GroupService $groupService,
                                EmailRepository $repository,
                                EntityManagerInterface $em,
                                MessageBusInterface $bus,
                                LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->groupService = $groupService;
        $this->bus = $bus;
        $this->em = $em;
        $this->sendingRepo = $this->em->getRepository(EmailSending::class);
    }

    public function __invoke(MailingGroupNotification $mailingNotification)
    {
        $id = $mailingNotification->getSendingId();
        $this->em->beginTransaction();
        $sending = $this->sendingRepo->findOneBy(['id' => $id]);
        if (empty($sending)) {
            $this->logger->notice("Sending with id {$id} was not found, canceling sending.");
            $this->em->rollback();

            return;
        }
        $template = $sending->getTemplate();
        $this->logger->info("Sending Message {$template->getName()}");
        $messages = [];
        try {
            $users = $this->groupService->query($template->getRecipientGroup());
            $sending->setStarted(new DateTime());
            $sending->setRecipientCount(sizeof($users));
            foreach ($users as $u) {
                $recipient = EmailRecipient::fromUser($u);
                if (empty($recipient)) {
                    $this->logger->warning("Skipping invalid email recipient {$u->getUuid()}");
                    continue;
                }
                $this->em->persist(
                    (new EmailSendingItem())
                        ->setGuid($recipient->getUuid())
                        ->setTries(0)
                        ->setSending($sending)
                );
                $messages[] = new MailingNotification($sending->getId(), $recipient);
            }
            $this->em->flush();
        } catch (Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to start Email sending', ['exception' => $e]);
            throw $e;
        }
        $this->em->commit();
        foreach ($messages as $message) {
            $this->bus->dispatch($message);
        }
    }
}
