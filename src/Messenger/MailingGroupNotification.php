<?php

namespace App\Messenger;

class MailingGroupNotification
{
    private int $sendingId;

    public function __construct(int $sendingId)
    {
        $this->sendingId = $sendingId;
    }

    public function getSendingId(): int
    {
        return $this->sendingId;
    }
}