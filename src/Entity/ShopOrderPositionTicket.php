<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class ShopOrderPositionTicket extends ShopOrderPosition
{
    #[ORM\OneToOne(mappedBy: 'shopOrderPosition', cascade: ['persist'])]
    private ?Ticket $ticket = null;

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        // unset the owning side of the relation if necessary
        if ($ticket === null && $this->ticket !== null) {
            $this->ticket->setShopOrderPosition(null);
        }

        // set the owning side of the relation if necessary
        if ($ticket !== null && $ticket->getShopOrderPosition() !== $this) {
            $ticket->setShopOrderPosition($this);
        }

        $this->ticket = $ticket;

        return $this;
    }

    public function getText(): ?string
    {
        if (empty($this->ticket)) {
            return "Ticket";
        } else {
            $nr = $this->ticket->getId();
            return "Ticket #{$nr}";
        }
    }
}