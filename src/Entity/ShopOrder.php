<?php

namespace App\Entity;

use App\Repository\ShopOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: ShopOrderRepository::class)]
class ShopOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'uuid')]
    private ?UuidInterface $orderer = null;

    #[ORM\Column(type: 'integer', enumType: ShopOrderStatus::class)]
    private ?ShopOrderStatus $status = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: ShopOrderPosition::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    private Collection $shopOrderPositions;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: ShopOrderHistory::class, cascade: ['persist', 'remove'], fetch: 'LAZY', orphanRemoval: true)]
    private Collection $shopOrderHistory;

    public function __construct()
    {
        $this->shopOrderPositions = new ArrayCollection();
        $this->shopOrderHistory = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getOrderer(): ?UuidInterface
    {
        return $this->orderer;
    }

    public function setOrderer(UuidInterface $orderer): static
    {
        $this->orderer = $orderer;

        return $this;
    }

    public function getStatus(): ?ShopOrderStatus
    {
        return $this->status;
    }

    public function setStatus(ShopOrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->status == ShopOrderStatus::Paid;
    }

    public function isCanceled(): bool
    {
        return $this->status == ShopOrderStatus::Canceled;
    }

    public function isOpen(): bool
    {
        return $this->status == ShopOrderStatus::Created;
    }

    /**
     * @return Collection<int, ShopOrderPosition>
     */
    public function getShopOrderPositions(): Collection
    {
        return $this->shopOrderPositions;
    }

    public function addShopOrderPosition(ShopOrderPosition $shopOrderPosition): static
    {
        if (!$this->shopOrderPositions->contains($shopOrderPosition)) {
            $this->shopOrderPositions->add($shopOrderPosition);
            $shopOrderPosition->setOrder($this);
        }

        return $this;
    }

    public function removeShopOrderPosition(ShopOrderPosition $shopOrderPosition): static
    {
        if ($this->shopOrderPositions->removeElement($shopOrderPosition)) {
            // set the owning side to null (unless already changed)
            if ($shopOrderPosition->getOrder() === $this) {
                $shopOrderPosition->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ShopOrderHistory>
     */
    public function getShopOrderHistory(): Collection
    {
        return $this->shopOrderHistory;
    }

    public function addShopOrderHistory(ShopOrderHistory $shopOrderHistory): static
    {
        if (!$this->shopOrderHistory->contains($shopOrderHistory)) {
            $this->shopOrderHistory->add($shopOrderHistory);
            $shopOrderHistory->setOrder($this);
        }

        return $this;
    }

    public function removeShopOrderHistory(ShopOrderHistory $shopOrderHistory): static
    {
        if ($this->shopOrderHistory->removeElement($shopOrderHistory)) {
            // set the owning side to null (unless already changed)
            if ($shopOrderHistory->getOrder() === $this) {
                $shopOrderHistory->setOrder(null);
            }
        }

        return $this;
    }

    public function calculateTotal(): int
    {
        $sum = 0;
        foreach ($this->shopOrderPositions as $position) {
            $sum += $position->getPrice() ?? 0;
        }
        return $sum;
    }

    public function isEmpty(): bool
    {
        return count($this->shopOrderPositions) == 0;
    }
}
