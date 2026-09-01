<?php

namespace App\Exception;

use App\Entity\Ticket;
use RuntimeException;

class TicketLifecycleException extends RuntimeException
{
    public readonly Ticket $ticketCode;

    public function __construct(Ticket $ticket, $message = '')
    {
        parent::__construct($message);

        $this->ticketCode = $ticket;
    }
}