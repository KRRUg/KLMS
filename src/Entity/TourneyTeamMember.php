<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class TourneyTeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid')]
    private ?UuidInterface $gamer = null;

    #[ORM\Column]
    private ?bool $accepted = null;

    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TourneyTeam $team = null;

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

    public function isAccepted(): ?bool
    {
        return $this->accepted;
    }

    public function setAccepted(bool $accepted): self
    {
        $this->accepted = $accepted;

        return $this;
    }

    public function getTeam(): ?TourneyTeam
    {
        return $this->team;
    }

    public function setTeam(?TourneyTeam $team): self
    {
        $this->team = $team;

        return $this;
    }

    public static function create(?UuidInterface $uuid): self
    {
        return (new self())->setAccepted(false)->setGamer($uuid);
    }
}
