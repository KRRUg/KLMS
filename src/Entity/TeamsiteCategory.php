<?php

namespace App\Entity;

use App\Repository\TeamsiteCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'teamsite_category')]
#[ORM\UniqueConstraint(name: 'teamsite_category_ord_unique', columns: ['teamsite_id', 'ord'])]
#[ORM\Entity(repositoryClass: TeamsiteCategoryRepository::class)]
class TeamsiteCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private ?int $ord = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(name: 'teamsite_id', nullable: false)]
    private ?Teamsite $teamsite = null;

    #[ORM\OneToMany(targetEntity: TeamsiteEntry::class, mappedBy: 'category', orphanRemoval: true, cascade: ['all'])]
    #[ORM\OrderBy(['ord' => 'ASC'])]
    private Collection $entries;

    #[ORM\Column(type: 'boolean')]
    private ?bool $hideEmail = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $hideName = null;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTeamsite(): ?Teamsite
    {
        return $this->teamsite;
    }

    public function setTeamsite(?Teamsite $teamsite): self
    {
        $this->teamsite = $teamsite;

        return $this;
    }

    /**
     * @return Collection|TeamsiteEntry[]
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(TeamsiteEntry $entry): self
    {
        if (!$this->entries->contains($entry)) {
            $this->entries[] = $entry;
            $entry->setCategory($this);
        }

        return $this;
    }

    public function removeEntry(TeamsiteEntry $entry): self
    {
        if ($this->entries->removeElement($entry)) {
            // set the owning side to null (unless already changed)
            if ($entry->getCategory() === $this) {
                $entry->setCategory(null);
            }
        }

        return $this;
    }

    public function getHideEmail(): ?bool
    {
        return $this->hideEmail;
    }

    public function setHideEmail(bool $hideEmail): self
    {
        $this->hideEmail = $hideEmail;

        return $this;
    }

    public function getHideName(): ?bool
    {
        return $this->hideName;
    }

    public function setHideName(bool $hideName): self
    {
        $this->hideName = $hideName;

        return $this;
    }
}
