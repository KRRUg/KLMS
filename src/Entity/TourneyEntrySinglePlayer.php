<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'tourney_entry_sp_once', columns: ['tourney', 'player'])]
class TourneyEntrySinglePlayer extends TourneyEntry
{
    #[ORM\Column(type: 'uuid')]
    private ?UuidInterface $gamer = null;

    public function getGamer(): ?UuidInterface
    {
        return $this->gamer;
    }

    public function setGamer(?UuidInterface $gamer): self
    {
        $this->gamer = $gamer;

        return $this;
    }
}