<?php

namespace App\Entity;

use App\Repository\TeamsiteEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Table(name: 'teamsite_entry')]
#[ORM\UniqueConstraint(name: 'teamsite_entry_ord_unique', columns: ['category_id', 'ord'])]
#[ORM\Entity(repositoryClass: TeamsiteEntryRepository::class)]
class TeamsiteEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid')]
    private ?UuidInterface $userUuid = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private ?int $ord = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false)]
    private ?TeamsiteCategory $category = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $displayEmail = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserUuid()
    {
        return $this->userUuid;
    }

    public function setUserUuid($userUuid): self
    {
        $this->userUuid = $userUuid;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOrd(): ?int
    {
        return $this->ord;
    }

    public function setOrd(int $ord): self
    {
        $this->ord = $ord;

        return $this;
    }

    public function getCategory(): ?TeamsiteCategory
    {
        return $this->category;
    }

    public function setCategory(?TeamsiteCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDisplayEmail(): ?string
    {
        return $this->displayEmail;
    }

    public function setDisplayEmail(?string $displayEmail): self
    {
        $this->displayEmail = $displayEmail;

        return $this;
    }
}
