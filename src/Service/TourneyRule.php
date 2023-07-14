<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use App\Entity\TourneyRules;

abstract class TourneyRule
{
    public function __construct(
        protected readonly Tourney $tourney,
    ){}

    /**
     * @param TourneyTeam[] $list
     */
    public abstract function seed(array $list): void;

    /**
     * @param TourneyGame $game
     * @param bool $overwrite Allows to clear and modify played games
     */
    public abstract function processGame(TourneyGame $game, bool $overwrite): void;

    /**
     * @return TourneyTeam[] Returns the podium (up to three places)
     */
    public abstract function podium(): array;

    /**
     * @return TourneyGame The finale of the tourney.
     */
    public function getFinal(): ?TourneyGame
    {
        foreach ($this->tourney->getGames() as $game) {
            if (is_null($game->getParent()))
                return $game;
        }
        return null;
    }

    /**
     * @return bool Returns true if all games are played.
     */
    public function completed(): bool
    {
        $finale = $this->getFinal();
        return !is_null($finale) && $finale->isDone();
    }

    public static function construct(Tourney $tourney): self
    {
        return match($tourney->getMode()) {
            TourneyRules::RegistrationOnly => new TourneyRuleNone($tourney),
            TourneyRules::DoubleElimination => new TourneyRuleDoubleElimination($tourney),
            TourneyRules::SingleElimination => new TourneyRuleSingleElimination($tourney),
        };
    }

    private static function ld(int $n): int
    {
        if ($n < 2)
            return 0;
        $n--;
        for ($i = 0; $n > 1; $i++)
            $n >>= 1;
        return $i + 1;
    }

    protected static function seedList(array $a): array
    {
        $limit = self::ld(count($a)) + 1;
        $r = array();
        $fun = function (int $seed, int $level) use (&$fun, $limit, $a, &$r) {
            $sum = (2 ** $level) + 1;
            if ($limit == $level + 1) {
                $r[] = $a[$seed - 1] ?? null;
                $r[] = $a[$sum - $seed - 1] ?? null;
            } elseif ($seed % 2) {
                $fun($seed, $level + 1);
                $fun($sum - $seed, $level + 1);
            } else {
                $fun($sum - $seed, $level + 1);
                $fun($seed, $level + 1);
            }
        };
        $fun(1, 1);
        return $r;
    }

    /**
     * @param Array<TourneyTeam|null> $chunk of size 2
     */
    protected function makeNode(array $chunk): TourneyGame|TourneyTeam
    {
        list($a, $b) = $chunk;

        if (is_null($a))
            return $b;
        if (is_null($b))
            return $a;

        $r = new TourneyGame();
        if ($a instanceof TourneyTeam) {
            $r->setTeamA($a);
        } elseif ($a instanceof TourneyGame) {
            $r->addChild($a->setIsChildA(true));
        }
        if ($b instanceof TourneyTeam) {
            $r->setTeamB($b);
        } elseif ($b instanceof TourneyGame) {
            $r->addChild($b->setIsChildA(false));
        }
        $this->tourney->addGame($r);
        return $r;
    }
}