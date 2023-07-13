<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use LogicException;

class TourneyRuleNone extends TourneyRule
{
    public function __construct(Tourney $tourney)
    {
        parent::__construct($tourney);
    }

    public function seed(array $list): void
    {
        throw new LogicException('invalid operation');
    }

    public function processGame(TourneyGame $game, bool $overwrite): void
    {
        throw new LogicException('invalid operation');
    }

    public function podium(): array
    {
        return [];
        // TODO: Implement podium() method.
    }

    public function completed(): bool
    {
        return false;
        // TODO: Implement completed() method.
    }

    public function getFinale(): ?TourneyGame
    {
        return null;
        // TODO: Implement getFinale() method.
    }
}