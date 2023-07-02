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

    // TODO remove unnecessary Tourney repositories
    public function __construct(
        TourneyRepository $repository,
        SettingService $settings,
        EntityManagerInterface $em,
    ) {
        parent::__construct($settings);
        $this->repository = $repository;
        $this->em = $em;
    }

    protected static function getSettingKey(): string
    {
        return 'lan.tourney.enabled';
    }

    public function getVisibleTourneys(): array
    {
        return $this->repository->findBy(['hidden' => false], ['order' => 'asc']);
    }

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

    public function getRegisteredTourneys(User $user)
    {
        return $this->repository->getTourneysByUser($user->getUuid());
    }
}