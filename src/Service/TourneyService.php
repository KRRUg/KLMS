<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyStatus;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use App\Entity\TourneyTeamMember;
use App\Entity\User;
use App\Exception\ServiceException;
use App\Repository\TourneyGameRepository;
use App\Repository\TourneyRepository;
use App\Repository\TourneyTeamMemberRepository;
use App\Repository\TourneyTeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use LogicException;

class TourneyService extends OptimalService
{
    private readonly EntityManagerInterface $em;
    private readonly TourneyRepository $repository;
    private readonly TourneyGameRepository $gameRepository;
    private readonly TourneyTeamRepository $teamRepository;
    private readonly TourneyTeamMemberRepository $teamMemberRepository;
    private readonly SettingService $settings;
    private readonly GamerService $gamerService;

    public function __construct(
        TourneyRepository $repository,
        TourneyGameRepository $gameRepository,
        TourneyTeamRepository $teamRepository,
        TourneyTeamMemberRepository $teamMemberRepository,
        SettingService $settings,
        GamerService $gamerService,
        EntityManagerInterface $em,
    ) {
        parent::__construct($settings);
        $this->repository = $repository;
        $this->gameRepository = $gameRepository;
        $this->teamRepository = $teamRepository;
        $this->teamMemberRepository = $teamMemberRepository;
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

    public function getTourneyWithTeams(int $id): ?Tourney
    {
        try {
            return $this->repository->getTourneyWithTeams($id);
        } catch (NoResultException|NonUniqueResultException){
            return null;
        }
    }

    public function getTourneyTeamMember(int $id): ?TourneyTeamMember
    {
        try {
            return $this->teamMemberRepository->find($id);
        } catch (NoResultException|NonUniqueResultException) {
            return null;
        }
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
     * @param User $user
     * @return TourneyTeamMember[]
     */
    public function getRegisteredTeams(User $user): array
    {
        return $this->teamMemberRepository->getTeamMemberByUser($user->getUuid());
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

    public function userCanModifyRegistration(Tourney $tourney): bool
    {
        try {
            $this->tryModifyRegistration($tourney);
            return true;
        } catch (ServiceException) {
            return false;
        }
    }

    private function teamNameTaken(string $name): bool
    {
        return $this->teamRepository->count(['name' => $name]) > 0;
    }

    public function userRegister(Tourney $tourney, User $user, TourneyTeam|string|null $team): void
    {
        $registered = $this->getRegisteredTourneys($user);
        $this->tryRegister($tourney, $registered);

        if ($tourney->isSinglePlayer()) {
            $tourney->addTeam(TourneyTeam::createTeamWithUser($user->getUuid()));
        } else {
            if ($team instanceof TourneyTeam) {
                if ($team->getTourney() !== $tourney) {
                    throw new LogicException('Invalid TourneyTeam specified.');
                }
                if ($team->countUsers() >= $tourney->getTeamsize()) {
                    throw new ServiceException(ServiceException::CAUSE_FULL, 'Team is already full');
                }
                $team->addMember(TourneyTeamMember::create($user->getUuid()));
            } elseif (is_string($team)) {
                if ($this->teamNameTaken($team)) {
                    throw new ServiceException(ServiceException::CAUSE_INCONSISTENT, 'Teamname already exists');
                }
                $tourney->addTeam(TourneyTeam::createTeamWithUser($user->getUuid(), $team));
            } else {
                throw new ServiceException(ServiceException::CAUSE_INVALID, 'Invalid team specified');
            }
        }
        $this->em->persist($tourney);
        $this->em->flush();
    }

    public function userConfirm(Tourney $tourney, User $user, User $admin, bool $accept): void
    {
        $ttm = $this->getTeamMemberByTourneyAndUser($tourney, $user);
        $this->userConfirmTeamMember($ttm, $admin, $accept);
    }

    public function userConfirmTeamMember(TourneyTeamMember $ttm, User $admin, bool $accept): void
    {
        $tourney = $ttm->getTeam()->getTourney();
        $this->tryModifyRegistration($tourney);

        $this->em->beginTransaction();
        $team = $ttm->getTeam();
        if ($ttm->isAccepted()) {
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'User is already accepted.');
        }
        if (!$this->teamRepository->userInTeam($team, $admin->getUuid(), true)) {
            throw new ServiceException(ServiceException::CAUSE_FORBIDDEN, 'User not authorized to confirm other user.');
        }
        if ($tourney->isSinglePlayer()) {
            throw new LogicException('Cannot confirm in single player tournament.');
        } else {
            if ($accept) {
                $ttm->setAccepted(true);
            } else {
                $team->removeMember($ttm);
            }
        }
        $this->em->flush();
        $this->em->commit();
    }

    public function userUnregister(Tourney $tourney, User $user): void
    {
        $this->tryModifyRegistration($tourney);
        $this->em->beginTransaction();
        $tm = $this->getTeamMemberByTourneyAndUser($tourney, $user);
        $team = $tm->getTeam();

        // orphan removal removes $tm when removing $team
        if ($tourney->isSinglePlayer()) {
            $this->teamRepository->remove($team);
        } else {
            $team->removeMember($tm);
            if ($team->countUsers() == 0) {
                $this->teamRepository->remove($team);
            } else {
                $this->teamMemberRepository->remove($tm);
            }
        }
        $this->em->flush();
        $this->em->commit();
    }

    public function getTeamMemberByTourneyAndUser(Tourney $tourney, User $user): TourneyTeamMember
    {
        $tm = $this->teamMemberRepository->getTeamMemberByUser($user->getUuid(), $tourney);
        if (empty($tm)) {
            throw new ServiceException(ServiceException::CAUSE_DONT_EXIST, 'User is not registered.');
        } elseif (count($tm) > 1) {
            throw new LogicException('More than one team per user and tourney.');
        }
        return $tm[0];
    }

    private function tryModifyRegistration(Tourney $tourney): void
    {
        if ($tourney->getStatus() != TourneyStatus::registration) {
            throw new ServiceException(ServiceException::CAUSE_IN_USE, 'Tourney registration is not open');
        }
    }

    private function tryRegister(Tourney $tourney, array $registeredTourneys, ?int $availToken = null): void
    {
        $this->tryModifyRegistration($tourney);
        $availToken ??= $this->calcRemainingToken($registeredTourneys);
        if (in_array($tourney, $registeredTourneys)) {
            throw new ServiceException(ServiceException::CAUSE_EXIST, 'User is already registered.');
        }
        if ($tourney->getToken() > $availToken) {
            throw new ServiceException(ServiceException::CAUSE_FORBIDDEN, 'User has not enough tokens left.');
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

    public function getAllUsersOfTourney(Tourney $tourney): array
    {
        $result = array();
        foreach ($tourney->getTeams() as $team) {
            foreach ($team->getMembers() as $member) {
                $result[] = $member->getGamer();
            }
        }
        return array_filter($result, fn($u) => !is_null($u));
    }
}