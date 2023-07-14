<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Exception\ServiceException;
use LogicException;

class TourneyRuleDoubleElimination extends TourneyRule
{
    public function __construct(Tourney $tourney)
    {
        parent::__construct($tourney);
    }

    // see https://www.printyourbrackets.com/

    public function seed(array $list): void
    {
        $count = count($list);
        if ($count < 3)
            throw new ServiceException(ServiceException::CAUSE_INCONSISTENT, 'at least three teams are required');

        $winner = self::seedList($list);
        $winner = array_reverse($winner);
        $winner = array_map(fn ($c) => $this->makeNode($c), array_chunk($winner, 2));
        $loser = $this->gamesToCallable($winner);
        while (count($winner) > 1) {
            // reduce
            $loser = array_map(fn ($c) => $this->makeLoserRound($c), array_chunk($loser, 2));
            $winner = array_map(fn ($c) => $this->makeNode($c), array_chunk($winner, 2));
            // merge
            $loser = self::mergeArray($loser, self::splitAndSwap($this->gamesToCallable($winner)));
            $loser = array_map(fn ($c) => $this->makeLoserRound($c), array_chunk($loser, 2));
        }
        $this->makeNode([$winner[0], $loser[0]]);
    }

    private static function mergeArray(array $a, array $b): array
    {
        if (count($a) != count($b))
            throw new LogicException('incorrect merge size');
        $r = array();
        for ($i = 0; $i < count($a); $i++) {
            $r[] = $a[$i];
            $r[] = $b[$i];
        }
        return $r;
    }

    private static function splitAndSwap(array $a): array
    {
        if (count($a) == 1)
            return $a;
        $b = array_chunk($a, count($a)/2);
        return array_merge($b[1],$b[0]);
    }

    private function gamesToCallable(array $games): array
    {
        $result = array();
        foreach ($games as &$game) {
            if ($game instanceof TourneyGame) {
                $result[] = function (TourneyGame $loserGame, bool $isA) use (&$game) {
                    $game->setLoserNext($loserGame)->setIsLoserNextA($isA);
                };
            } else {
                $result[] = null;
            }
        }
        return $result;
    }

    /**
     * @param Array<TourneyGame|callable> $list Two games from the winners bracket
     * @return TourneyGame|callable|null Game in the loser bracket
     */
    private function makeLoserRound(array $list): TourneyGame|callable|null
    {
        list($a, $b) = $list;

        if (is_null($a))
            return $b;
        if (is_null($b))
            return $a;

        $r = (new TourneyGame());
        if (is_callable($a))
            $a($r, true);
        else
            $r->addChild($a->setIsChildA(true));

        if (is_callable($b))
            $b($r, false);
        else
            $r->addChild($b->setIsChildA(false));

        $this->tourney->addGame($r);
        return $r;
    }

    public function processGame(TourneyGame $game, bool $overwrite): void
    {
        // if the winner of the loser bracket (side B) wins the finale, there is a second finale
        if ($game === $this->getFinal()) {
            if (count($game->getChildren()) == 2 && $game->hasWon(false)) {
                $nf = (new TourneyGame())
                    ->addChild($game->setIsChildA(true))
                    ->setTeamA($game->getTeamA())
                    ->setTeamB($game->getTeamB());
                $this->tourney->addGame($nf);
            }
        } else {
            $parent = $game->getParent();
            if ($game->isChildA()) {
                $parent->setTeamA($game->getWinner());
            } else {
                $parent->setTeamB($game->getWinner());
            }
            if ($loserNext = $game->getLoserNext()) {
                if ($game->isIsLoserNextA()) {
                    $loserNext->setTeamA($game->getLoser());
                } else {
                    $loserNext->setTeamB($game->getLoser());
                }
            }
        }
    }

    public function podium(): array
    {
        $root = $this->getFinal();
        if (is_null($root))
            return [];

        $result = array();
        $result[1] = [$root->getWinner()];
        $result[2] = [$root->getLoser()];
        if (count($root->getChildren()) === 2) {
            $result[3] = [$root->getChild(false)->getLoser()];
        } else {
            $result[3] = [$root->getChild(true)->getChild(false)->getLoser()];
        }

        return $result;
    }

    public static function getOriginalFinale(TourneyGame $finale): TourneyGame
    {
        if (count($finale->getChildren()) == 1) {
            return $finale->getChildren()[0];
        }
        return $finale;
    }
}