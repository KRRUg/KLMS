<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\User;
use App\Repository\TourneyRepository;
use Doctrine\ORM\EntityManagerInterface;

class TourneyService extends OptimalService
{
    private readonly EntityManagerInterface $em;
    private readonly TourneyRepository $repository;
    private readonly SettingService $settings;

    // TODO remove unnecessary Tourney repositories
    public function __construct(
        TourneyRepository $repository,
        SettingService $settings,
        EntityManagerInterface $em,
    ) {
        parent::__construct($settings);
        $this->repository = $repository;
        $this->settings = $settings;
        $this->em = $em;
    }

    public const TOKEN_COUNT = 20;
    private const SETTING_PREFIX = 'lan.tourney.';

    protected static function getSettingKey(): string
    {
        return self::SETTING_PREFIX.'enabled';
    }

    /* Find/Manage Tourney */

    public function getVisibleTourneys(): array
    {
        return $this->repository->findBy(['hidden' => false], ['order' => 'asc']);
    }

    /**
     * @param User $user
     * @return Tourney[]
     */
    public function getRegisteredTourneys(User $user): array
    {
        return $this->repository->getTourneysByUser($user->getUuid());
    }

    public function calculateUserToken(User $user): int
    {
        // TODO use this once setting can handle int
        // $tokens = $this->settings->get(self::SETTING_PREFIX.'tokens');
        $tokens = self::TOKEN_COUNT;
        foreach ($this->getRegisteredTourneys($user) as $tourney) {
            $tokens -= $tourney->getToken();
        }
        return max(0, $tokens);
    }

    /* Tourney tree */

    public static function getFinal(Tourney $tourney): ?TourneyGame
    {
        foreach ($tourney->getGames() as $game) {
            if (is_null($game->getParent()))
                return $game;
        }
        return null;
    }

    public static function calculateRounds(Tourney $tourney): int
    {
        $depth = function(TourneyGame $g) use (&$depth) {
            if ($g->getChildren()->isEmpty()) return 1;
            return max(array_map(fn($c) => $depth($c) + 1, $g->getChildren()->toArray()));
        };
        $final = self::getFinal($tourney);
        if (is_null($final))
            return 0;
        return $depth($final);
    }
}