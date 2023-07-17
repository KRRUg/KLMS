<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
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
        $root = $this->getFinal();
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