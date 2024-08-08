<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use App\Service\TicketState;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'uuid', unique: true, nullable: true)]
    private ?UuidInterface $redeemer = null;

    #[ORM\Column]
    #[Assert\LessThanOrEqual('now')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'createdAt')]
    #[Assert\LessThanOrEqual('now')]
    private ?\DateTimeImmutable $redeemedAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'redeemedAt')]
    #[Assert\LessThanOrEqual('now')]
    private ?\DateTimeImmutable $punchedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getRedeemer(): ?UuidInterface
    {
        return $this->redeemer;
    }

    public function setRedeemer(?UuidInterface $redeemer): static
    {
        $this->redeemer = $redeemer;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getRedeemedAt(): ?\DateTimeImmutable
    {
        return $this->redeemedAt;
    }

    public function setRedeemedAt(?\DateTimeImmutable $redeemedAt): static
    {
        $this->redeemedAt = $redeemedAt;

        return $this;
    }

    public function getPunchedAt(): ?\DateTimeImmutable
    {
        return $this->punchedAt;
    }

    public function setPunchedAt(?\DateTimeImmutable $punchedAt): static
    {
        $this->punchedAt = $punchedAt;

        return $this;
    }

    public function isRedeemed(): bool
    {
        return $this->redeemer != null;
    }

    public function isPunched(): bool
    {
        return $this->punchedAt != null;
    }

    public function getState(): ?TicketState
    {
        if ($this->isRedeemed() && $this->isPunched()) {
            return TicketState::PUNCHED;
        } else if ($this->isRedeemed() && !$this->isPunched()) {
            return TicketState::REDEEMED;
        } else if (!$this->isRedeemed() && !$this->isPunched()) {
            return TicketState::NEW;
        } else {
            return null;
        }
    }
}
