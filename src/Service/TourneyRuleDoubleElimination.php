<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;

class TourneyRuleDoubleElimination extends TourneyRule
{
    public function __construct(Tourney $tourney)
    {
        parent::__construct($tourney);
    }

    public function seed(array $list): void
    {
        // TODO: Implement seed() method.
    }

    public function processGame(TourneyGame $game, bool $overwrite): void
    {
        // TODO: Implement processGame() method.
    }

    public function podium(): array
    {
        // TODO: Implement podium() method.
    }

    public function completed(): bool
    {
        // TODO: Implement completed() method.
    }

    public function getFinale(): ?TourneyGame
    {
        // TODO: Implement getFinale() method.
    }
}