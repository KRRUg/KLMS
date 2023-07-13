<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use App\Exception\ServiceException;
use LogicException;

class TourneyRuleSingleElimination extends TourneyRule
{
    public function __construct(Tourney $tourney)
    {
        parent::__construct($tourney);
    }

    public function seed(array $list): void
    {
        $this->tourney->getGames()->clear();
        $count = count($list);
        if ($count < 3)
            throw new ServiceException(ServiceException::CAUSE_INCONSISTENT, 'at least two teams are required');

        $list = self::expandList($list);
        $games = array_map(fn ($c) => $this->makeLeaf($c), array_chunk($list, 4));
        while (count($games) > 1) {
            $games = array_map(fn($c) => $this->makeNode($c), array_chunk($games, 2));
        }
        list($this->finale) = $games;
    }

    /**
     * @param TourneyTeam[] $chunk of size 4
     * @return TourneyGame
     */
    private function makeLeaf(array $chunk): TourneyGame
    {
        list($a, $b, $c, $d) = $chunk;
        if ($a && $b && $c && $d) {
            $g1 = (new TourneyGame())->setTeamA($a)->setTeamB($b)->setIsChildA(true);
            $g2 = (new TourneyGame())->setTeamA($c)->setTeamB($d)->setIsChildA(false);
            $r = (new TourneyGame())->addChild($g1)->addChild($g2);
            $this->tourney->addGame($g1)->addGame($g2)->addGame($r);
            return $r;
        } elseif ($a && $b && $c && !$d) {
            $g1 = (new TourneyGame())->setTeamA($a)->setTeamB($b)->setIsChildA(true);
            $r = (new TourneyGame())->setTeamB($c)->addChild($g1);
            $this->tourney->addGame($g1)->addGame($r);
            return $r;
        } elseif ($a && !$b && $c && !$d) {
            $r = (new TourneyGame())->setTeamA($a)->setTeamB($c);
            $this->tourney->addGame($r);
            return $r;
        } else {
            throw new LogicException('This should not have happened.');
        }
    }

    /**
     * @param TourneyGame[] $chunk of size 2
     * @return TourneyGame
     */
    private function makeNode(array $chunk): TourneyGame
    {
        list($a, $b) = $chunk;
        $r = (new TourneyGame())
            ->addChild($a->setIsChildA(true))
            ->addChild($b->setIsChildA(false))
        ;
        $this->tourney->addGame($r);
        return $r;
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
        $root = $this->getFinale();
        if (is_null($root))
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

    public function completed(): bool
    {
        $finale = $this->getFinale();
        return !is_null($finale) && $finale->isDone();
    }

    public function getFinale(): ?TourneyGame
    {
        foreach ($this->tourney->getGames() as $game) {
            if (is_null($game->getParent()))
                return $game;
        }
        return null;
    }
}