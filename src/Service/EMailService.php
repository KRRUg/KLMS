<?php


namespace App\Service;


use App\Entity\Admin\EMail\EmailSendingTask;
use App\Entity\Admin\EMail\EMailTemplate;
use App\Entity\HelperEntities\EMailRecipient;
use App\Repository\Admin\EMail\EmailSendingTaskRepository;
use App\Repository\Admin\EMail\EMailSendingRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EMailService
{
    protected $mailer;
    protected $senderAddress;
    protected $sendings;
    protected $sendingRecipients;
    protected $em;
    protected $logger;
    protected $recipients = [];
    protected $queueSending = false;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, EMailSendingRepository $sendingRepository, EmailSendingTaskRepository $emailSendingRecipientRepository)
    {
        $this->mailer = $mailer;
        $this->queueSending = $_ENV['MAILER_QUEUESENDING'] == 'true';
        $mailAddress = $_ENV['MAILER_DEFAULT_SENDER_EMAIL'];
        $mailName = $_ENV['MAILER_DEFAULT_SENDER_NAME'];
        $this->senderAddress = new Address($mailAddress, $mailName);
        $this->logger = $logger;
        //repos
        $this->recipients = $emailSendingRecipientRepository;
        $this->sendings = $sendingRepository;
    }


    /*
     * Helper Methods
     */
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

    public function addRecipient($user = null) // TODO: Dann mal mit echten UserDaten füllen
    {
        $recipient = new  EMailRecipient('Hansi', 'hansi@hansi.at');
        array_push($this->recipients, $recipient);
    }

    public function getTemplateVariables()
    {
        $draft = new  EMailRecipient('Hansi', 'hansi@hansi.at');
        return $draft->getDataArray();
    }


    /*
     * Sending methods
     */
    public function sendAll(EMailTemplate $template)
    {
        if (count($this->recipients) <= 0) {
            throw  new \Exception("Recipient list is empty!");
        }
        foreach ($this->recipients as $recipient) {
            $this->sendEMail($template, $recipient);
        }
    }

    public function sendSingleEmail(EMailTemplate $template, EMailRecipient $mailRecipient)
    {
        $this->sendEMail($template, $mailRecipient);
    }

    private function sendEMail(EMailTemplate $template, EMailRecipient $recipient)
    {
        try {
            $text = $this->replaceVariableTokens($template->getBody(), $recipient);
            $email = (new Email())
                ->from($this->senderAddress)
                ->to($recipient->getAddressObject())
                ->subject($template->getSubject())
                ->text(strip_tags($text))
                ->html($text);
            if ($this->queueSending == true) {
                // TODO QUEUE-SENDING implementieren
            } else {
                $this->mailer->send($email);
            }
        } catch (\Exception $e) {
            $this->logger->error(' QUEUE Sending: ' . $this->queueSending . ' --> ' . $e);
            if (!$this->queueSending)
                throw  $e;
        }
    }
}
