<?php


namespace App\Messenger;


use App\Helper\EmailRecipient;

class MailingHookNotification
{
    private string $hook;
    private EmailRecipient $recipient;
    private array $context;

    public function __construct(string $hook, EmailRecipient $recipient, array $context = [])
    {
        $this->hook = $hook;
        $this->recipient = $recipient;
        $this->context = $context;
    }

    public function getHook(): string
    {
        return $this->hook;
    }

    public function getRecipient(): EmailRecipient
    {
        return $this->recipient;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}