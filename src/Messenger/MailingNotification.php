<?php

namespace App\Messenger;

use App\Helper\EmailRecipient;

class MailingNotification
{
    private readonly int $sendingId;
    private readonly EmailRecipient $recipient;

    public function __construct(int $sending, EmailRecipient $recipient)
    {
        $this->sendingId = $sending;
        $this->recipient = $recipient;
    }

    public function getSendingId(): int
    {
        return $this->sendingId;
    }

    public function getRecipient(): EmailRecipient
    {
        return $this->recipient;
    }
}
