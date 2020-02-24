<?php


namespace App\Service;


use App\Entity\Admin\EMail\EMailTemplate;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EMailService
{

    protected $mailer;
    protected $senderAddress;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        $mailAddress = $_ENV['MAILER_DEFAULT_SENDER_EMAIL'];
        $mailName = $_ENV['MAILER_DEFAULT_SENDER_NAME'];
        $this->senderAddress = new Address($mailAddress, $mailName);
    }

    public function sendEMail(EMailTemplate $template, string $recipientAddress)
    {
        $email = (new Email())
            ->from($this->senderAddress)
            ->to($recipientAddress)
            ->subject($template->getSubject())
            ->text($template->getBody())
            ->html($template->getBody());
        $this->mailer->send($email);
    }

}
