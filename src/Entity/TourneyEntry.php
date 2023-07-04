<?php

namespace App\Entity;

use App\Repository\TourneyTeamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'tourney_entry')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 1)]
#[ORM\DiscriminatorMap(['t' => TourneyEntryTeam::class, 's' => TourneyEntrySinglePlayer::class])]
#[ORM\Entity(repositoryClass: TourneyTeamRepository::class)]
abstract class TourneyEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tourney $tourney = null;

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

    abstract public function getUserUuids(): array;

    abstract public function countUsers(): int;
}
