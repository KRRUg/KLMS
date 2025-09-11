<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use LogicException;

class TourneyRuleNone extends TourneyRule
{
    public function __construct(Tourney $tourney, SettingService $settingService)
    {
        parent::__construct($tourney, $settingService);
    }

    public function seed(array $list): void
    {
        throw new LogicException('invalid operation');
    }

    public function processGame(TourneyGame $game, bool $overwrite): void
    {
        throw new LogicException('invalid operation');
    }

    public function getTrees(): array
    {
        return [];
    }

    public function isCompleted(): bool
    {
        throw new LogicException('invalid operation');
    }

    public function getFinal(): ?TourneyGame
    {
        throw new LogicException('invalid operation');
    }

    public function podium(): array
    {
        $root = null;
        foreach ($this->tourney->getGames() as $game) {
            if (is_null($game->getParent()))
                $root = $game;
        }
        if (is_null($root) || !$root->isDone())
            return [];
        $result = array();
        $result[1] = [$root->getWinner()];
        $result[2] = [$root->getLoser()];
        $child = $root->getChild(true);
        if ($child)
            $result[3] = [$child->getWinner()];
        return $result;
    }

    public function setPodium(TourneyTeam $first, TourneyTeam $second, ?TourneyTeam $third): void
    {
        $t = (new TourneyGame())
            ->setTeamA($first)->setScoreA(1)
            ->setTeamB($second)->setScoreB(0);
        $this->tourney->addGame($t);
        if (!is_null($third)) {
            $tt = ((new TourneyGame())
                ->setTeamA($third)->setScoreA(1)
                ->setTeamB($third)->setScoreB(0)
                ->setIsChildA(true)
            );
            $t->addChild($tt);
            $this->tourney->addGame($tt);
        }
    }
}