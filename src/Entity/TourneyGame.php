<?php

namespace App\Entity;

use App\Repository\TourneyGameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
    private ?TourneyTeam $teamA = null;

    #[ORM\ManyToOne]
    private ?TourneyTeam $teamB = null;

    #[ORM\Column(nullable: true)]
//    #[Assert\NotEqualTo(propertyPath: 'scoreB')]
    private ?int $scoreA = null;

    #[ORM\Column(nullable: true)]
//    #[Assert\NotEqualTo(propertyPath: 'scoreA')]
    private ?int $scoreB = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    // root node ignores this property
    #[ORM\Column(name: 'left_child', nullable: false)]
    private bool $isChildA = true;

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

    public function getTeamA(): ?TourneyTeam
    {
        return $this->teamA;
    }

    public function setTeamA(?TourneyTeam $teamA): self
    {
        $this->teamA = $teamA;

        return $this;
    }

    public function getTeamB(): ?TourneyTeam
    {
        return $this->teamB;
    }

    public function setTeamB(?TourneyTeam $teamB): self
    {
        $this->teamB = $teamB;

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

    /**
     * @return Collection<int, TourneyGame>
     */
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

    public function isChildA(): bool
    {
        return $this->isChildA;
    }

    public function setIsChildA(bool $isChildA): TourneyGame
    {
        $this->isChildA = $isChildA;

        return $this;
    }

    public function getChild(bool $childA): ?self
    {
        foreach ($this->children as $child) {
            /** @var self $child */
            if ($child->isChildA() == $childA)
                return $child;
        }
        return null;
    }

    public function isDone(): bool
    {
        return !is_null($this->getScoreA()) && !is_null($this->getScoreB());
    }

    public function hasWon(bool $teamA): bool
    {
        if (!$this->isDone())
            return false;
        return ($this->getScoreA() > $this->getScoreB()) == $teamA;
    }

    public function isPending(): bool
    {
        return (is_null($this->getScoreA()) || is_null($this->getScoreB()))
            && (!is_null($this->getTeamA()) && !is_null($this->getTeamB()));
    }

    public function getWinner(): ?TourneyTeam
    {
        if ($this->hasWon(true)) {
            return $this->getTeamA();
        } elseif ($this->hasWon(false)) {
            return $this->getTeamB();
        } else {
            return null;
        }
    }

    public function getLoser(): ?TourneyTeam
    {
        if ($this->hasWon(true)) {
            return $this->getTeamB();
        } elseif ($this->hasWon(false)) {
            return $this->getTeamA();
        } else {
            return null;
        }
    }
}
