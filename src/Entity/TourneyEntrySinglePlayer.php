<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: 'tourney_entry_sp_once', columns: ['tourney', 'player'])]
class TourneyEntrySinglePlayer extends TourneyEntry
{
    #[ORM\Column(type: 'uuid')]
    private ?UuidInterface $player = null;

    public function getPlayer(): ?UuidInterface
    {
        return $this->player;
    }

    public function setPlayer(?UuidInterface $player): self
    {
        $this->player = $player;

        return $this;
    }
}