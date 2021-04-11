<?php


namespace App\Messenger;


use App\Helper\EmailRecipient;

class MailingHookNotification
{
    private string $hook;
    private EmailRecipient $recipient;

    public function __construct(string $hook, EmailRecipient $recipient)
    {
        $this->hook = $hook;
        $this->recipient = $recipient;
    }

    public function getHook(): string
    {
        return $this->hook;
    }

    public function getRecipient(): EmailRecipient
    {
        return $this->recipient;
    }
}