<?php

namespace App\Entity;

use App\Repository\TourneyGameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TourneyGameRepository::class)]
// ensures that every node can have at most two children, tourney_id is required to have multiple root nodes
#[ORM\UniqueConstraint(name: 'tourney_tree_structure', columns: ['tourney_id', 'parent', 'left_child'])]
class TourneyGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(name: 'tourney_id', nullable: false)]
    private ?Tourney $tourney = null;

    #[ORM\ManyToOne]
    private ?TourneyEntry $entryA = null;

    #[ORM\ManyToOne]
    private ?TourneyEntry $entryB = null;

    #[ORM\Column(nullable: true)]
    private ?int $scoreA = null;

    #[ORM\Column(nullable: true)]
    private ?int $scoreB = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent', nullable: true)]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    // root node ignores this property
    #[ORM\Column(name: 'left_child', nullable: false)]
    private ?bool $isChildA = true;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTourney(): ?Tourney
    {
        return $this->tourney;
    }

    public function setTourney(?Tourney $tourney): self
    {
        $this->tourney = $tourney;

        return $this;
    }

    public function getEntryA(): ?TourneyEntry
    {
        return $this->entryA;
    }

    public function setEntryA(?TourneyEntry $entryA): self
    {
        $this->entryA = $entryA;

        return $this;
    }

    public function getEntryB(): ?TourneyEntry
    {
        return $this->entryB;
    }

    public function setEntryB(?TourneyEntry $entryB): self
    {
        $this->entryB = $entryB;

        return $this;
    }

    public function getScoreA(): ?int
    {
        return $this->scoreA;
    }

    public function setScoreA(?int $scoreA): self
    {
        $this->scoreA = $scoreA;

        return $this;
    }

    public function getScoreB(): ?int
    {
        return $this->scoreB;
    }

    public function setScoreB(?int $scoreB): self
    {
        $this->scoreB = $scoreB;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getIsChildA(): ?bool
    {
        return $this->isChildA;
    }

    public function setIsChildA(?bool $isChildA): TourneyGame
    {
        $this->isChildA = $isChildA;

        return $this;
    }
}
