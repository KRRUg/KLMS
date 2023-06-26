<?php

namespace App\Entity;

use App\Repository\TourneyTeamMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: TourneyTeamMemberRepository::class)]
class TourneyTeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid')]
    private ?UuidInterface $gamer = null;

    #[ORM\Column]
    private ?bool $captain = null;

    #[ORM\Column]
    private ?bool $accepted = null;

    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TourneyEntryTeam $team = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGamer(): ?UuidInterface
    {
        return $this->gamer;
    }

    public function setGamer(UuidInterface $gamer): self
    {
        $this->gamer = $gamer;

        return $this;
    }

    public function isCaptain(): ?bool
    {
        return $this->captain;
    }

    public function setCaptain(bool $captain): self
    {
        $this->captain = $captain;

        return $this;
    }

    public function isAccepted(): ?bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): self
    {
        $this->accepted = $accepted;

        return $this;
    }

    public function getTeam(): ?TourneyEntry
    {
        return $this->team;
    }

    public function setTeam(?TourneyEntry $team): self
    {
        $this->team = $team;

        return $this;
    }
}
