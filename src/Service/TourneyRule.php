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
     * @return bool Returns true if all games are played.
     */
    public abstract function completed(): bool;

    /**
     * @return TourneyGame The finale of the tourney.
     */
    public abstract function getFinale(): ?TourneyGame;

    public static function construct(Tourney $tourney): self
    {
        return match($tourney->getMode()) {
            TourneyRules::RegistrationOnly => new TourneyRuleNone($tourney),
            TourneyRules::DoubleElimination => new TourneyRuleDoubleElimination($tourney),
            TourneyRules::SingleElimination => new TourneyRuleSingleElimination($tourney),
        };
    }

    protected static function nextPow(int $n): int
    {
        if ($n < 2)
            return 1;
        $n--;
        for ($i = 0; $n > 1; $i++)
            $n >>= 1;
        return 1 << ($i + 1);
    }

    protected static function expandList(array $a): array
    {
        $n = count($a);
        $n = self::nextPow($n) - $n;
        $r = array();
        foreach (array_reverse($a) as $t) {
            if ($n-- > 0)
                $r[] = null;
            $r[] = $t;
        }
        return array_reverse($r);
    }
}