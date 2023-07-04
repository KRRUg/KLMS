<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyEntry;
use App\Entity\TourneyEntrySinglePlayer;
use App\Entity\TourneyEntryTeam;
use App\Entity\TourneyGame;
use App\Entity\User;
use App\Exception\ServiceException;
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

    public const TOKEN_COUNT = 40;
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

    /**
     * @param Tourney[] $tourneys
     * @return int
     */
    private function calcRemainingToken(array $tourneys): int
    {
        // TODO use this once setting can handle int
        // $tokens = $this->settings->get(self::SETTING_PREFIX.'tokens');
        $tokens = self::TOKEN_COUNT;
        foreach ($tourneys as $tourney) {
            $tokens -= $tourney->getToken();
        }
        return $tokens;
    }

    public function calculateUserToken(User $user): int
    {
        return max(0, $this->calcRemainingToken($this->getRegisteredTourneys($user)));
    }

    /* Tourney registration */

    private function tryRegister(Tourney $tourney, User $user): void
    {
        if ($tourney->isStarted()) {
            throw new ServiceException(ServiceException::CAUSE_IN_USE, 'Tourney has already started');
        }
        $tourneys = $this->getRegisteredTourneys($user);
        if (in_array($tourney, $tourneys)) {
            throw new ServiceException(ServiceException::CAUSE_EXIST, 'User is already registered.');
        }
        if ($tourney->getToken() > $this->calcRemainingToken($tourneys)) {
            throw new ServiceException(ServiceException::CAUSE_EMPTY, 'User has not enough tokens left.');
        }
    }

    public function userCanRegisterForTourney(Tourney $tourney, User $user): bool
    {
        try {
            $this->tryRegister($tourney, $user);
            return true;
        } catch (ServiceException) {
            return false;
        }
    }

    public function getTourneyTeams(Tourney $tourney): array
    {
        if ($tourney->isSinglePlayer()) {
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'Singleplayer tourney does not have teams');
        }
        $teams = $tourney->getEntries()->toArray();
        foreach ($teams as $team) {
            if (!($team instanceof TourneyEntryTeam))
                throw new ServiceException(ServiceException::CAUSE_INCONSISTENT, 'Incorrect TourneyEntry type found');
        }
        return $teams;
    }

    public function registerForTourney(Tourney $tourney, User $user): bool
    {
        $this->tryRegister($tourney, $user);
        // TODO implement me
//        $entry = $tourney->getTeamsize() == 1
//            ? (new TourneyEntrySinglePlayer())->setGamer($user->getUuid())
//            : (n)
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