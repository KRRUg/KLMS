<?php

namespace App\Messenger;

use App\Entity\EmailSending;
use App\Entity\EmailSendingItem;
use App\Helper\EMailRecipient;
use App\Repository\EMailRepository;
use App\Service\EMailService;
use App\Service\GroupService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MailingGroupNotificationHandler implements MessageHandlerInterface
{
    private EMailService $mailService;
    private GroupService $groupService;
    private ObjectRepository $sendingRepo;
    private EntityManagerInterface $em;
    private MessageBusInterface $bus;
    private LoggerInterface $logger;

    public function __construct(EMailService $mailService,
                                GroupService $groupService,
                                EMailRepository $repository,
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
            $sending->setStarted(new \DateTime());
            $sending->setRecipientCount(sizeof($users));
            foreach ($users as $u) {
                $recipient = EMailRecipient::fromUser($u);
                if (empty($recipient)) {
                    $this->logger->warning("Skipping invalid email recipient {$u->getUuid()}");
                    continue;
                }
                $this->em->persist(
                    (new EmailSendingItem())
                    ->setGuid($recipient->getUuid())
                    ->setSending($sending)
                );
                $messages[] = new MailingNotification($sending->getId(), $recipient);
            }
            $this->em->flush();
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error("Failed to start Email sending", ['exception' => $e]);
            throw $e;
        }
        $this->em->commit();
        foreach ($messages as $message) {
            $this->bus->dispatch($message);
        }
    }
}
