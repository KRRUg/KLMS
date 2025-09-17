<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Exception\ServiceException;

class TourneyRuleSingleElimination extends TourneyRule
{
    public function __construct(Tourney $tourney, SettingService $settingService)
    {
        parent::__construct($tourney, $settingService);
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
        // generate small final if desired
        if ($count >= 4 && $this->settingService->get('lan.tourney.small_final')) {
            $this->tourney->addGame(new TourneyGame());
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

            // if the next game is the finale, put the loser to the small final if it exists
            $finale = $this->getFinal();
            if ($parent === $finale) {
                $smallFinal = $this->getSmallFinal();
                if (!is_null($smallFinal)) {
                    if ($game->isChildA()) {
                        $smallFinal->setTeamA($game->getLoser());
                    } else {
                        $smallFinal->setTeamB($game->getLoser());
                    }
                }
            }
        }
    }

    public function podium(): array
    {
        $final = $this->getFinal();
        $smallFinal = $this->getSmallFinal();
        if (is_null($final) || !$final->isDone() || (!is_null($smallFinal) && !$smallFinal->isDone()))
            return [];

        $result = array();
        $result[1] = [$final->getWinner()];
        $result[2] = [$final->getLoser()];
        if (!is_null($smallFinal)) {
            $result[3] = [$smallFinal->getWinner()];
        } else {
            $result[3] = array();
            foreach ($final->getChildren() as $child) {
                $result[3][] = $child->getLoser();
            }
        }
        return $result;
    }

    public function getFinal(): ?TourneyGame
    {
       foreach ($this->tourney->getGames() as $game) {
           if (is_null($game->getParent()) && !$game->getChildren()->isEmpty()) {
               return $game;
           }
       }
       return null;
    }

    public function getSmallFinal(): ?TourneyGame
    {
        foreach ($this->tourney->getGames() as $game) {
            if (is_null($game->getParent()) && $game->getChildren()->isEmpty()) {
                return $game;
            }
        }
        return null;
    }

    public function getTrees(): array
    {
        $tree = array();
        $final = $this->getFinal();
        $smallFinal = $this->getSmallFinal();

        // find proper tree
        $tree[''] = [$final, -1];

        // find game for 3rd place
        if ($smallFinal)
            $tree['3rd Place'] = [$smallFinal, 1];

        return $tree;
    }

    public function isCompleted(): bool
    {
        $final = $this->getFinal();
        $smallFinal = $this->getSmallFinal();

        return $final != null && $final->isDone() && ($smallFinal == null || $smallFinal->isDone());
    }
}
