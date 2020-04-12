<?php


namespace App\Service;


use App\Entity\EMail\EmailSending;
use App\Entity\EMail\EmailSendingTask;
use App\Entity\EMail\EMailTemplate;
use App\Entity\HelperEntities\EMailRecipient;
use App\Repository\EMail\EmailSendingTaskRepository;
use App\Repository\EMail\EMailSendingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use App\Security\User;

/**
 * Class EMailService
 * @package App\Service
 */
//TODO: Exceptions abfangen
class EMailService
{
    protected $mailer;
    protected $senderAddress;
    protected $recipients = [];
    protected $sendingRecipients;
    protected $em;
    protected $logger;
    protected $tasks;
    protected $sendings;
    protected $queueSending = false;
    protected $testRecipient;
    protected $isInTestMode;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        EMailSendingRepository $sendingRepository,
        EmailSendingTaskRepository $emailSendingTaskRepository,
        EntityManagerInterface $em
    )
    {
        $this->mailer = $mailer;
        $this->queueSending = $_ENV['MAILER_QUEUESENDING'] == 'true';
        $mailAddress = $_ENV['MAILER_DEFAULT_SENDER_EMAIL'];
        $mailName = $_ENV['MAILER_DEFAULT_SENDER_NAME'];
        $this->senderAddress = new Address($mailAddress, $mailName);
        $this->logger = $logger;
        $this->em = $em;
        //repos
        $this->tasks = $emailSendingTaskRepository;
        $this->sendings = $sendingRepository;
    }

    /*
     * Sending + Sending Task Verwaltung
     */
    public function createSending(EmailSending $sending, EMailTemplate $template, $clone = true)
    {
        // Erstellt ein neues Template mit, dann kann nichts passieren, wenn ein Template während einer Aussendung geändert wird
        ///Bei ApplicationHook kein Sending kopieren!
        if ($clone && !$sending->isApplicationHooked())
            $template = clone $template;

        $this->em->persist($template);
        $sending->setTemplate($template);
        $this->em->persist($sending);

        //Keine sendinglist, weil ApplicationHook nur Single-Mail ist
        if (!$sending->isApplicationHooked()) {
            //Welchen Gruppen??
            $this->getPossibleEmailRecipients();
            $this->createSendingTasks($sending);
        }


        $this->em->persist($sending);
        $this->em->flush();
        return $sending;
    }

    /**
     * @param EmailSending $sending
     */
    private function createSendingTasks(EmailSending $sending)
    {
        foreach ($this->recipients as $recipient) {
            $task = new EmailSendingTask();
            $task->setRecipient($recipient);
            $task->setIsSendable(true);
            $this->em->persist($task);
            $sending->addEmailSendingTask($task);
        }
        $this->em->flush();
    }

    public function stopSending(EmailSending $sending)
    {
        $tasks = $sending->getEMailSendingTask();
        foreach ($tasks as $task) {
            if ($task->getIsSent() == false) {
                $task->setIsSendable('false');
                $this->em->persist($task);
                //sofort flushen, damit keine EMails mehr ausgesendet werden können
                $this->em->flush();
            }
        }
    }

    //TODO: Mockdaten gegen echte Daten austauschen
    public function getPossibleEmailRecipients($group = null)
    {
        for ($i = 0; $i < 50; $i++) {
            $this->addRecipient();
        }
        return $this->recipients;
    }

    /*
     * Helper Methods
     */

    public function previewTemplate(EMailTemplate $template)
    {
        $this->addRecipient();
        $html = $template->getBody();
        $testRecipient = $this->generateTestRecipient();
        $html = $this->replaceVariableTokens($html, $testRecipient);
        $template->setSubject($this->replaceVariableTokens($template->getSubject(), $testRecipient));
        $template->setBody($html);
        return $template;
    }

    private function replaceVariableTokens($text, EMailRecipient $mailRecipient)
    {
        $errors = [];
        $recipientData = $mailRecipient->getDataArray();
        preg_match_all('/{{2}.*}{2}/', $text, $matches);
        if (isset($matches[0]) && count($matches[0])) {
            foreach ($matches[0] as $match) {
                $variableName = str_replace('{', '', $match);
                $variableName = str_replace('}', '', $variableName);
                $variableName = trim(strtolower($variableName));
                $filled = $recipientData[$variableName];
                if (empty($filled)) {
                    $error = "Variable $variableName not valid: " . sprintf($mailRecipient);
                    $this->logger->error($error);
                    array_push($errors, $error);
                }
                if (count($errors) > 0)
                    throw  new  \Exception("MailData is not valid: " . implode(',', $errors));
                $text = str_replace($match, $filled, $text);
            }
        }
        return $text;
    }

    //TODO: private machen!
    public function addRecipient(User $user) // TODO: Dann mal mit echten UserDaten füllen
    {
        if ($user == null) {
            $recipient = $this->generateTestRecipient();
            $this->isInTestMode = true;
        } else {
            $recipient = new EMailRecipient($user->getUuid(), $user->getUsername(), $user->getEmail());
        }
        array_push($this->recipients, $recipient);
    }

    private function removeRecipients()
    {
        $this->recipients = [];
    }

    public function getTemplateVariables()
    {
        $testRecipient = $this->generateTestRecipient();
        return $testRecipient->getDataArray();
    }

    private function generateTestRecipient()
    {
        return new  EMailRecipient(md5(rand()), 'Name ' . rand(), 'hansi' . rand() . '@krru.at');
    }

    /*
     * Sending methods
     */

    public function sendEmailTasks($limit = null)
    {
        $tasks = $this->tasks->findBy(['isSent' => false, 'isSendable' => true], ['created' => 'ASC'], $limit);
        $lastSending = null;
        $template = null;
        foreach ($tasks as $task) {
            $sending = $task->getEMailSending();

            //Template nur neu lkaden, wenn sich das Sending geändert hat
            if ($sending != $lastSending) {
                $template = $sending->getTemplate();
            } else {
                //prüfen ob alle gesendet wurden, dann sending closen
                $opentasks = $this->tasks->findBy(['emailSending' => $lastSending, 'isSent' => false, 'isSendable' => true], ['created' => 'ASC']);
                if (count($opentasks) == 0) {
                    $sending->setSent();
                    $this->em->persist($sending);
                    $this->em->flush();
                }
            }
            if ($sending != $lastSending) {
                if ($lastSending == null)
                    $this->setSendingStatus($sending);
                else
                    $this->setSendingStatus($lastSending);
            }

            $lastSending = $sending;

            if ($sending->getReadyToSend() && $template->getIsPublished()) {
                $recipient = $task->getRecipient();
                $sendingError = $this->sendEMail($template, $recipient);
                if ($sendingError == null) {
                    $task->setIsSent();
                    $this->em->persist($task);
                    $this->em->flush();
                }
            }
        }

    }

    public function sendByApplicationHook(string $applicationHook, EMailRecipient $recipient)
    {
        $sending = $this->sendings->findOneBy(['ApplicationHook' => $applicationHook]);
        $template = $sending->getTemplate();
        $this->sendSingleEmail($template, $recipient);
    }

    public function repairSendingStats()
    {
        foreach ($this->sendings->findAll() as $sending) {
            $this->setSendingStatus($sending);
        }
    }

    private function setSendingStatus(EmailSending $sending)
    {
        //prüfen ob alle gesendet wurden, dann sending closen
        $opentasks = $this->tasks->findBy(['emailSending' => $sending, 'isSent' => false, 'isSendable' => true], ['created' => 'ASC']);
        if ($opentasks == null || count($opentasks) == 0) {
            $lastTask = $this->tasks->findOneBy(['emailSending' => $sending], ['sent' => 'DESC']);
            if ($lastTask != null)
                $sending->setSent($lastTask->getSent());
            $this->em->persist($sending);
            $this->em->flush();
        }
    }

    public function sendSingleEmail(EMailTemplate $template, EMailRecipient $mailRecipient)
    {
        $this->sendEMail($template, $mailRecipient);
    }

    private function sendEMail(EMailTemplate $template, EMailRecipient $recipient)
    {
        $error = null;
        if ($this->isInTestMode) {
            $error = "Sending was in Test-Mode or no User was given!";
            $this->logger->alert($error);
        }
        if ($error != null)
            return $error;
        try {
            $text = $this->replaceVariableTokens($template->getBody(), $recipient);
            $subject = $this->replaceVariableTokens($template->getSubject(), $recipient);
            $email = (new Email())
                ->from($this->senderAddress)
                ->to($recipient->getAddressObject())
                ->subject($subject)
                ->text(strip_tags($text))
                ->html($text);
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error($e);
            $error = $e;
        } finally {
            return $error;
        }
    }

    public function deleteTemplate(EMailTemplate $template)
    {
        if ($template->getIsDeleteable()) {
            $this->deleteSending($template->getEmailSending());
            $this->em->remove($template);
            $this->em->flush();
        }
    }

    public function deleteSending(EmailSending $sending = null)
    {
        if ($sending != null && $sending->getIsDeleteable()) {
            $this->deleteSendingTasks($sending);
            $this->em->remove($sending);
            $this->em->flush();
        }
    }

    private function deleteSendingTasks(EmailSending $sending)
    {
        $tasks = $sending->getEMailSendingTask();
        foreach ($tasks as $task) {
            $this->em->remove($task);
        }
    }


}
