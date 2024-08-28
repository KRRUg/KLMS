<?php

namespace App\Twig;

use App\Entity\Clan;
use App\Entity\Seat;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\GroupService;
use App\Service\SeatmapService;
use App\Service\TicketService;
use App\Service\UserService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class UserExtension extends AbstractExtension
{
    private readonly IdmRepository $userRepo;
    private readonly IdmRepository $clanRepo;
    private readonly UserService $userService;
    private readonly SeatmapService $seatmapService;
    private readonly TicketService $ticketService;

    public function __construct(IdmManager $manager, UserService $userService, SeatmapService $seatmapService, TicketService $ticketService)
    {
        $this->userRepo = $manager->getRepository(User::class);
        $this->clanRepo = $manager->getRepository(Clan::class);
        $this->userService = $userService;
        $this->seatmapService = $seatmapService;
        $this->ticketService = $ticketService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTests(): array
    {
        return [
            new TwigTest('valid_user', $this->validUser(...)),
            new TwigTest('registered_user', $this->userIsRegistered(...)),
            new TwigTest('seated_user', $this->userIsSeated(...)),
            new TwigTest('in_clan', $this->userIsInClan(...)),
            new TwigTest('in_clans', $this->userIsInClans(...)),
            new TwigTest('age_below', $this->userAgeBelow(...)),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('user', $this->getUser(...)),
            new TwigFilter('clan', $this->getClan(...)),
            new TwigFilter('username', $this->getUserName(...)),
            new TwigFilter('user_image', $this->getUserImage(...)),
            new TwigFilter('group_name', $this->getGroupName(...)),
            new TwigFilter('seat', $this->getSeat(...)),
        ];
    }

    public function getUser($userId): ?User
    {
        if (empty($userId) || !Uuid::isValid($userId)) {
            return null;
        }

        return $this->userRepo->findOneById($userId);
    }

    public function getClan($clanId): ?Clan
    {
        if (empty($clanId) || !Uuid::isValid($clanId)) {
            return null;
        }

        return $this->clanRepo->findOneById($clanId);
    }

    public function getUserName($userId): string
    {
        $user = $this->getUser($userId);

        if (empty($user)) {
            return '';
        }

        return $user->getNickname();
    }

    public function getGroupName($groupUuid): string
    {
        if (empty($groupUuid) || !Uuid::isValid($groupUuid)) {
            return '';
        }

        return GroupService::getName(Uuid::fromString($groupUuid));
    }

    public function getUserImage(?User $user): string
    {
        if (empty($user)) {
            return '';
        }
        return $this->userService->getUserImage($user) ?? '';
    }

    public function validUser($userId): bool
    {
        return !empty($this->getUser($userId));
    }

    public function userIsRegistered(User|UuidInterface $user): bool
    {
        return $this->ticketService->isUserRegistered($user);
    }

    public function userIsSeated(User|UuidInterface $user): bool
    {
        return $this->seatmapService->getUserSeatCount($user) > 0;
    }

    public function userIsInClan(User|UuidInterface $user, Clan|UuidInterface $clan): bool
    {
        return $this->userService->isUserInClan($user, $clan);
    }

    /**
     * @param User|UuidInterface $user
     * @param Clan[]|UuidInterface[] $clans
     * @return bool
     */
    public function userIsInClans(User|UuidInterface $user, array $clans): bool
    {
        return $this->userService->isUserInClans($user, $clans);
    }

    public function userAgeBelow(User|UuidInterface $user, int $age): bool
    {
        return !($this->userService->userAgeAbove($user, $age) ?? true);
    }

    public function getSeat(User $user): string
    {
        $seats = $this->seatmapService->getUserSeats($user);
        $names = array_map(fn (Seat $seat) => $seat->generateSeatName(), $seats);

        return implode(',', $names);
    }
}
