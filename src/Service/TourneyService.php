<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyStage;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use App\Entity\TourneyTeamMember;
use App\Entity\TourneyRules;
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
use Psr\Log\LoggerInterface;

class TourneyService extends OptimalService
{
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;
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
        LoggerInterface $logger,
    ) {
        parent::__construct($settings);
        $this->repository = $repository;
        $this->gameRepository = $gameRepository;
        $this->teamRepository = $teamRepository;
        $this->teamMemberRepository = $teamMemberRepository;
        $this->settings = $settings;
        $this->gamerService = $gamerService;
        $this->em = $em;
        $this->logger = $logger;
    }

    // TODO log everything

    public const TOKEN_COUNT = 40;
    public const TEAM_NAME_MAX_LENGTH = 25;
    public const TOURNEY_MIN_SIZE = 3;

    private const SETTING_PREFIX = 'lan.tourney.';

    protected static function getSettingKey(): string
    {
        return self::SETTING_PREFIX.'enabled';
    }

    /* Find/Manage Tourney */

    public function getAll(): array
    {
        return $this->repository->findBy([], ['status' => 'asc', 'order' => 'asc']);
    }

    /**
     * @return Tourney[]
     */
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

    public function getGame(int $id): ?TourneyGame
    {
        try {
            return $this->gameRepository->find($id);
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

    /**
     * @param User $user
     * @return TourneyGame[]
     */
    public function getPendingGames(User $user): array
    {
        return $this->gameRepository->findActiveGamesByUser($user->getUuid(), true);
    }

    /**
     * @param User $user
     * @return TourneyGame[]
     */
    public function getActiveGames(User $user): array
    {
        return $this->gameRepository->findActiveGamesByUser($user->getUuid());
    }

    /* Tourney registration */

    public function registrationOpen(): bool
    {
        return $this->settings->get(self::SETTING_PREFIX.'registration_open', false);
    }

    public function userMayParticipate(User $user): bool
    {
        return $this->gamerService->gamerIsOnLan($user);
    }

    public function getRegistrableTourneys($user): array
    {
        $registeredTourneys = $this->getRegisteredTourneys($user);
        $token = $this->calcRemainingToken($registeredTourneys);
        $filter = function (Tourney $tourney) use ($user, $registeredTourneys, $token) {
            try {
                $this->tryRegister($tourney, $user, $registeredTourneys, $token);
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
            $this->tryRegister($tourney, $user, $registered);
            return true;
        } catch (ServiceException) {
            return false;
        }
    }

    public function userCanModifyRegistration(Tourney $tourney, User $user): bool
    {
        try {
            $this->tryModifyRegistration($tourney, $user);
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
        $this->tryRegister($tourney, $user, $registered);

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
                if (strlen($team) > self::TEAM_NAME_MAX_LENGTH) {
                    throw new ServiceException(ServiceException::CAUSE_TOO_LONG, 'Teamname must be shorter than 25 chars');
                }
                $team = substr($team, 0, min(25, strlen($team)));
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
        if (is_null($ttm)) {
            throw new ServiceException(ServiceException::CAUSE_DONT_EXIST, 'User is not registered.');
        }
        $this->userConfirmTeamMember($ttm, $admin, $accept);
    }

    public function userConfirmTeamMember(TourneyTeamMember $ttm, User $admin, bool $accept): void
    {
        $tourney = $ttm->getTeam()->getTourney();
        $this->tryModifyRegistration($tourney, $admin);

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
        $this->tryModifyRegistration($tourney, $user);
        $this->em->beginTransaction();
        $tm = $this->getTeamMemberByTourneyAndUser($tourney, $user);
        if (is_null($tm)) {
            $this->em->rollback();
            throw new ServiceException(ServiceException::CAUSE_DONT_EXIST, 'User is not registered.');
        }
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

    public function getTeamMemberByTourneyAndUser(Tourney $tourney, User $user): ?TourneyTeamMember
    {
        $tm = $this->teamMemberRepository->getTeamMemberByUser($user->getUuid(), $tourney);
        if (empty($tm)) {
            return null;
        } elseif (count($tm) > 1) {
            throw new LogicException('More than one team per user and tourney.');
        }
        return $tm[0];
    }

    private function tryModifyRegistration(Tourney $tourney, User $user): void
    {
        if ($tourney->getStatus() != TourneyStage::Registration) {
            throw new ServiceException(ServiceException::CAUSE_IN_USE, 'Tourney registration is not open');
        }
        if (!$this->userMayParticipate($user)) {
            throw new ServiceException(ServiceException::CAUSE_FORBIDDEN, 'User is not on lan.');
        }
    }

    private function tryRegister(Tourney $tourney, User $user, ?array $registeredTourneys = null, ?int $availToken = null): void
    {
        $this->tryModifyRegistration($tourney, $user);
        if (!$this->registrationOpen()) {
            throw new ServiceException(ServiceException::CAUSE_FORBIDDEN, 'Registration is closed.');
        }
        $registeredTourneys ??= $this->getRegisteredTourneys($user);
        $availToken ??= $this->calcRemainingToken($registeredTourneys);
        if (in_array($tourney, $registeredTourneys)) {
            throw new ServiceException(ServiceException::CAUSE_EXIST, 'User is already registered.');
        }
        if ($tourney->getToken() > $availToken) {
            throw new ServiceException(ServiceException::CAUSE_FORBIDDEN, 'User has not enough tokens left.');
        }
    }

    /* Tourney tree */

    public static function getPodium(Tourney $tourney): array
    {
        return TourneyRule::construct($tourney)->podium();
    }
    
    /* Result logging */

    public function getGameByTourneyAndUser(Tourney $tourney, User $user): ?TourneyGame
    {
        $game = $this->gameRepository->findActiveGamesByUser($user->getUuid(), false, $tourney);
        if (empty($game)) {
            return null;
        } elseif (count($game) > 1) {
            throw new LogicException('More than one team per user and tourney.');
        }
        return $game[0];
    }

    private function tryLogResult(TourneyGame $game)
    {
        $tourney = $game->getTourney();
        if (is_null($tourney) || $tourney->getStatus() != TourneyStage::Running) {
            throw new ServiceException(ServiceException::CAUSE_IN_USE, 'Tourney is not running.');
        }
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

    public function logResultUser(TourneyGame $game, User $user, int $scoreA, int $scoreB)
    {
        $userInTeamA = $this->teamRepository->userInTeam($game->getTeamA(), $user->getUuid());
        $userInTeamB = $this->teamRepository->userInTeam($game->getTeamB(), $user->getUuid());

        if (!$userInTeamA && !$userInTeamB) {
            throw new ServiceException(ServiceException::CAUSE_INCONSISTENT, 'Only members of the teams are allowed to enter results');
        }
        if (($scoreA >= $scoreB && $userInTeamA) || ($scoreB >= $scoreA && $userInTeamB)) {
            throw new ServiceException(ServiceException::CAUSE_FORBIDDEN, 'Loser must enter the result');
        }
        $this->logResult($game, $scoreA, $scoreB);
    }

    public function logResult(TourneyGame $game, int $scoreA, int $scoreB)
    {
        $this->tryLogResult($game);
        if ($scoreA == $scoreB) {
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'Tie not allowed.');
        }
        $game->setScoreA($scoreA);
        $game->setScoreB($scoreB);
        TourneyRule::construct($game->getTourney())->processGame($game, false);
        $this->em->flush();
    }

    /* Tourney state management */

    private static function verifyStage(Tourney $tourney, TourneyStage $expected)
    {
        if ($tourney->getStatus() != $expected) {
            throw new ServiceException(ServiceException::CAUSE_INCORRECT_STATE, "Tourney {$tourney->getName()} is not in state {$expected->getMessage()}");
        }
    }

    public function start(Tourney $tourney)
    {
        self::verifyStage($tourney, TourneyStage::Created);
        $tourney->setStatus(TourneyStage::Registration);
        $this->em->flush();
    }

    /**
     * @param Tourney $tourney
     * @param TourneyTeam[]|null $seed
     */
    public function seed(Tourney $tourney, ?array $seed = null)
    {
        self::verifyStage($tourney, TourneyStage::Seeding);
        if (is_null($seed)) {
            $seed = $tourney->getTeams()->toArray();
            shuffle($seed);
        }
        if (count($seed) != count($tourney->getTeams())) {
            throw new ServiceException(ServiceException::CAUSE_INCONSISTENT, 'seed does not contain all teams');
        }
        if (count($seed) < self::TOURNEY_MIN_SIZE){
            throw new ServiceException(ServiceException::CAUSE_INVALID, "A tourney must contain at least " . self::TOURNEY_MIN_SIZE . " teams");
        }
        $this->em->beginTransaction();
        $this->clearGames($tourney);
        TourneyRule::construct($tourney)->seed($seed);
        $this->em->flush();
        $this->em->commit();
    }

    private function clearTeams(Tourney $tourney): void
    {
        $this->clearGames($tourney);
        $tourney->getTeams()->forAll(function($key, $team) {$this->em->remove($team); return true;});
        $this->em->flush();
    }

    private function clearGames(Tourney $tourney): void
    {
        $tourney->getGames()->forAll(function($key, $game) {$this->em->remove($game); return true;});
        $this->em->flush();
    }

    public function setResult(Tourney $tourney, TourneyTeam $first, TourneyTeam $second, ?TourneyTeam $third = null): void
    {
        self::verifyStage($tourney, TourneyStage::Running);
        $rules = TourneyRule::construct($tourney);
        if (!($rules instanceof TourneyRuleNone))
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'Cannot set result on seeded tourney');
        $this->em->beginTransaction();
        $this->clearGames($tourney);
        $rules->setPodium($first, $second, $third);
        $this->em->flush();
        $this->em->commit();
    }

    /**
     * Advance to the next state if no options are required.
     * @param Tourney $tourney
     * @throws ServiceException when no auto-advance is possible
     */
    public function advance(Tourney $tourney): void
    {
        switch ($tourney->getStatus()) {
            case TourneyStage::Created:
                $tourney->setStatus(TourneyStage::Registration);
                break;
            case TourneyStage::Registration:
                if ($tourney->getMode()->canHaveGames()){
                    $tourney->setStatus(TourneyStage::Seeding);
                    $this->seed($tourney);
                } else {
                    $tourney->setStatus(TourneyStage::Running);
                }
                break;
            case TourneyStage::Seeding:
                $tourney->setStatus(TourneyStage::Running);
                break;
            case TourneyStage::Running:
                $tourney->setStatus(TourneyStage::Finished);
                break;
            case TourneyStage::Finished:
            default:
                return;
        }
        $this->em->flush();
    }

    public function back(Tourney $tourney): void
    {
        switch ($tourney->getStatus()) {
            case TourneyStage::Created:
            default:
                return;
            case TourneyStage::Registration:
                $this->clearTeams($tourney);
                $tourney->setStatus(TourneyStage::Created);
                break;
            case TourneyStage::Seeding:
                $this->clearGames($tourney);
                $tourney->setStatus(TourneyStage::Registration);
                break;
            case TourneyStage::Running:
                if ($tourney->getMode()->canHaveGames()) {
                    $tourney->setStatus(TourneyStage::Seeding);
                    $this->seed($tourney);
                } else {
                    $tourney->setStatus(TourneyStage::Registration);
                }
                break;
            case TourneyStage::Finished:
                $tourney->setStatus(TourneyStage::Running);
                break;
        }
        $this->em->flush();
    }

    public static function getFinal(Tourney $tourney): ?TourneyGame
    {
        return TourneyRule::construct($tourney)->getFinal();
    }

    /* Tourney object management */

    public function delete(Tourney $tourney)
    {
        $this->repository->remove($tourney);
        $this->em->flush();
    }

    public function save(Tourney $tourney)
    {
        $this->repository->save($tourney);
        $this->em->flush();
    }
}