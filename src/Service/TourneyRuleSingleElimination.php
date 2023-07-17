<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Exception\ServiceException;

class TourneyRuleSingleElimination extends TourneyRule
{
    public function __construct(Tourney $tourney)
    {
        parent::__construct($tourney);
    }

    public function seed(array $list): void
    {
        $count = count($list);
        if ($count < 3)
            throw new ServiceException(ServiceException::CAUSE_INCONSISTENT, 'at least three teams are required');

        $list = self::seedList($list);
        while (count($list) > 1) {
            $list = array_map(fn($c) => $this->makeNode($c), array_chunk($list, 2));
        }
    }

    public function processGame(TourneyGame $game, bool $overwrite): void
    {
        $parent = $game->getParent();
        if (!is_null($parent)) {
            if ($game->isChildA()) {
                $parent->setTeamA($game->getWinner());
            } else {
                $parent->setTeamB($game->getWinner());
            }
        }
    }

    public function podium(): array
    {
        $root = $this->getFinal();
        if (is_null($root) || !$root->isDone())
            return [];
        $result = array();
        $result[1] = [$root->getWinner()];
        $result[2] = [$root->getLoser()];
        $result[3] = array();
        foreach ($root->getChildren() as $child) {
            $result[3][] = $child->getLoser();
        }
        return $result;
    }
}