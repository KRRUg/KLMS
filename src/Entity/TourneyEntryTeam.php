<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TourneyEntryTeam extends TourneyEntry
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TourneyTeamMember::class, orphanRemoval: true, cascade: ['persist'], fetch: 'EAGER')]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, TourneyTeamMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(TourneyTeamMember $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setTeam($this);
        }

        return $this;
    }

    public function removeMember(TourneyTeamMember $member): self
    {
        if ($this->members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getTeam() === $this) {
                $member->setTeam(null);
            }
        }

        return $this;
    }

    public function getUserUuids(): array
    {
        return array_map(fn ($m) => $m->getGamer(), $this->members->toArray());
    }

    public function countUsers(): int
    {
        return count($this->getMembers());
    }
}