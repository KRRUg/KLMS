<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class ShopOrderPositionTicket extends ShopOrderPosition
{
    #[ORM\ManyToOne(fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'ticket_id', unique: true, nullable: true)]
    private ?Ticket $ticket = null;

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getText(): ?string
    {
        if (empty($this->ticket)) {
            return "Ticket";
        } else {
            $code = $this->ticket->getCode();
            $nr = $this->ticket->getId();
            return "Ticket #{$nr} ({$code})";
        }
    }
}