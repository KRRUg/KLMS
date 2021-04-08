<?php

namespace App\Messenger;

use App\Helper\EMailRecipient;

class MailingNotification
{
    private int $sendingId;
    private EMailRecipient $recipient;

    public function __construct(int $sending, EMailRecipient $recipient)
    {
        $this->sendingId = $sending;
        $this->recipient = $recipient;
    }

    public function getSendingId(): int
    {
        return $this->sendingId;
    }

    public function getRecipient(): EMailRecipient
    {
        return $this->recipient;
    }
}