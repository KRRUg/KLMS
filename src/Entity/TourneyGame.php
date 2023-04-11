<?php

namespace App\Entity;

use App\Repository\TourneyGameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TourneyGameRepository::class)]
class TourneyGame
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tourney $tourney = null;

    #[ORM\ManyToOne]
    private ?TourneyTeam $teamA = null;

    #[ORM\ManyToOne]
    private ?TourneyTeam $teamB = null;

    #[ORM\Column(nullable: true)]
    private ?int $scoreA = null;

    #[ORM\Column(nullable: true)]
    private ?int $scoreB = null;

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
}
