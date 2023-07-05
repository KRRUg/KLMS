<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyStatus;
use App\Entity\TourneyTeam;
use App\Entity\TourneyGame;
use App\Entity\User;
use App\Exception\ServiceException;
use App\Repository\TourneyGameRepository;
use App\Repository\TourneyRepository;
use Doctrine\ORM\EntityManagerInterface;

class TourneyService extends OptimalService
{
    private readonly EntityManagerInterface $em;
    private readonly TourneyRepository $repository;
    private readonly TourneyGameRepository $gameRepository;
    private readonly SettingService $settings;
    private readonly GamerService $gamerService;

    // TODO remove unnecessary Tourney repositories
    public function __construct(
        TourneyRepository $repository,
        TourneyGameRepository $gameRepository,
        SettingService $settings,
        GamerService $gamerService,
        EntityManagerInterface $em,
    ) {
        parent::__construct($settings);
        $this->repository = $repository;
        $this->gameRepository = $gameRepository;
        $this->settings = $settings;
        $this->gamerService = $gamerService;
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

    public function getPendingGames(User $user): array
    {
        return $this->gameRepository->findPendingGamesByUser($user->getUuid());
    }

    /* Tourney registration */

    public function userMayRegister(User $user): bool
    {
        return $this->gamerService->gamerIsOnLan($user) && $this->settings->get(self::SETTING_PREFIX.'registration_open');
    }

    public function getRegistrableTourneys($user): array
    {
        if (!$this->userMayRegister($user))
            return [];

        $registeredTourneys = $this->getRegisteredTourneys($user);
        $token = $this->calcRemainingToken($registeredTourneys);
        $filter = function (Tourney $tourney) use ($registeredTourneys, $token) {
            try {
                $this->tryRegister($tourney, $registeredTourneys, $token);
                return true;
            } catch (ServiceException) {
                return false;
            }
        };
        return array_filter($this->getVisibleTourneys(), $filter);
    }

    public function userCanRegisterForTourney(Tourney $tourney, User $user): bool
    {
        $registered = $this->getRegisteredTourneys($user);
        try {
            $this->tryRegister($tourney, $registered);
            return true;
        } catch (ServiceException) {
            return false;
        }
    }

    private function tryRegister(Tourney $tourney, array $registeredTourneys, ?int $availToken = null): void
    {
        $availToken ??= $this->calcRemainingToken($registeredTourneys);
        if ($tourney->getStatus() != TourneyStatus::registration) {
            throw new ServiceException(ServiceException::CAUSE_IN_USE, 'Tourney registration is not open');
        }
        if (in_array($tourney, $registeredTourneys)) {
            throw new ServiceException(ServiceException::CAUSE_EXIST, 'User is already registered.');
        }
        if ($tourney->getToken() > $availToken) {
            throw new ServiceException(ServiceException::CAUSE_EMPTY, 'User has not enough tokens left.');
        }
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