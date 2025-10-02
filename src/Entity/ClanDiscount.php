<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClanDiscountRepository;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: ClanDiscountRepository::class)]
#[ORM\Table(name: "clan_discount")]
class ClanDiscount
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    private ?UuidInterface $id = null;
    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    #[ORM\Column(type: "uuid", nullable: false)]
    private ?UuidInterface $clan = null;

    #[ORM\Column(type: "integer")]
    private int $price;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getClanId(): ?UuidInterface
    {
        return $this->clan;
    }

    public function setClanId(?UuidInterface $clanId): self
    {
        $this->clan = $clanId;
        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }
}
