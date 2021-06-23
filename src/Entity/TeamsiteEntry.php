<?php

namespace App\Entity;

use App\Repository\TeamsiteEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=TeamsiteEntryRepository::class)
 * @ORM\Table(
 *     name="teamsite_entry",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(name="teamsite_entry_ord_unique", columns={"category_id", "ord" }),
 *     },
 * )
 */
class TeamsiteEntry
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="uuid")
     */
    private ?UuidInterface $userUuid;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $title = '';

    /**
     * @ORM\Column(type="text")
     */
    private ?string $description = '';

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $ord = 0;

    /**
     * @ORM\ManyToOne(targetEntity=TeamsiteCategory::class, inversedBy="entries")
     * @ORM\JoinColumn(name="category_id", nullable=false)
     */
    private ?TeamsiteCategory $category;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $displayEmail;


    public function __construct()
    {
    }

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
