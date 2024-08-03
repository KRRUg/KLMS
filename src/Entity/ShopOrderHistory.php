<?php

namespace App\Entity;

use App\Repository\ShopOrderHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: ShopOrderHistoryRepository::class)]
class ShopOrderHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shopOrderHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ShopOrder $order = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $loggedAt = null;

    #[ORM\Column(type: 'string', enumType: ShopOrderHistoryAction::class)]
    private ?ShopOrderHistoryAction $action = null;

    #[ORM\Column(length: 1024)]
    private ?string $text = '';

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?UuidInterface $loggedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?ShopOrder
    {
        return $this->order;
    }

    public function setOrder(?ShopOrder $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getLoggedAt(): ?\DateTimeImmutable
    {
        return $this->loggedAt;
    }

    public function setLoggedAt(\DateTimeImmutable $loggedAt): static
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    public function getAction(): ?ShopOrderHistoryAction
    {
        return $this->action;
    }

    public function setAction(ShopOrderHistoryAction $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getLoggedBy(): ?UuidInterface
    {
        return $this->loggedBy;
    }

    public function setLoggedBy(?UuidInterface $loggedBy): static
    {
        $this->loggedBy = $loggedBy;

        return $this;
    }
}
