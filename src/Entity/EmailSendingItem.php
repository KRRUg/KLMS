<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class EmailSendingItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private $guid;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: EmailSending::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', name: 'sending_id')]
    private $sending;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $success = null;

    #[ORM\Column(type: 'integer')]
    private int $tries = 0;

    public function getGuid(): ?UuidInterface
    {
        return $this->guid;
    }

    public function setGuid(UuidInterface $guid): self
    {
        $this->guid = $guid;

        return $this;
    }

    public function getSending(): ?EmailSending
    {
        return $this->sending;
    }

    public function setSending(?EmailSending $sending): self
    {
        $this->sending = $sending;

        return $this;
    }

    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function getTries(): ?int
    {
        return $this->tries;
    }

    public function setTries(int $tries): self
    {
        $this->tries = $tries;

        return $this;
    }
}
