<?php

namespace App\Entity;

use App\Repository\TourneyTeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TourneyTeamRepository::class)]
#[ORM\UniqueConstraint(name: 'tourney_team_name_unique', fields: ['tourney_id', 'name'])]
class TourneyTeam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'teams')]
    #[ORM\JoinColumn(nullable: false, name: 'tourney_id')]
    private ?Tourney $tourney = null;

    #[ORM\Column(length: 255, name: 'name', nullable: true)]
    #[Assert\Length(max: 255, maxMessage: '{{ field }} is too long.')]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TourneyTeamMember::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
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

    public static function createTeamWithUser(UuidInterface $user, string $name = null): self
    {
        return (new self())->setName($name)->addMember((new TourneyTeamMember())->setGamer($user)->setAccepted(true));
    }
}
