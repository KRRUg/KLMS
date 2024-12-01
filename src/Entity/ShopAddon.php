<?php

namespace App\Entity;

use App\Repository\ShopAddonsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopAddonsRepository::class)]
class ShopAddon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column(nullable: true)]
    private ?int $sortIndex = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $onlyOnce = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxQuantityGlobal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getSortIndex(): ?int
    {
        return $this->sortIndex;
    }

    public function setSortIndex(int $sortIndex): static
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getOnlyOnce(): ?bool
    {
        return $this->onlyOnce;
    }

    public function setOnlyOnce(?bool $onlyOnce): static
    {
        $this->onlyOnce = $onlyOnce;

        return $this;
    }

    public function getMaxQuantityGlobal(): ?int
    {
        return $this->maxQuantityGlobal;
    }

    public function setMaxQuantityGlobal(?int $maxQuantityGlobal): static
    {
        $this->maxQuantityGlobal = $maxQuantityGlobal;

        return $this;
    }
}
