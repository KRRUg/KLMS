<?php

namespace App\Service;

use App\Entity\Clan;
use App\Entity\Seat;
use App\Entity\SeatKind;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\SeatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\SecurityBundle\Security;

class SeatmapService
{
    // short-lived cache since seat assignments can change at any time, but this avoids
    // redundant IDM bulk calls for the many concurrent /seatmap page views in a short window
    private const IDM_CACHE_TTL = 45;

    private readonly EntityManagerInterface $em;
    private readonly SeatRepository $seatRepository;
    private readonly TicketService $ticketService;
    private readonly Security $security;
    private readonly IdmManager $manager;
    private readonly IdmRepository $userRepo;
    private readonly IdmRepository $clanRepo;
    private readonly SettingService $settingService;
    private readonly UserService $userService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SeatRepository         $seatRepository,
        IdmManager             $manager,
        Security               $security,
        TicketService          $ticketService,
        SettingService         $settingService,
        UserService            $userService)
    {
        $this->em = $entityManager;
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->clanRepo = $manager->getRepository(Clan::class);
        $this->seatRepository = $seatRepository;
        $this->security = $security;
        $this->ticketService = $ticketService;
        $this->settingService = $settingService;
        $this->userService = $userService;
    }

    public function getSeatmap(): array
    {
        return $this->seatRepository->findAll();
    }

    /**
     * Preloads the owners and clan reservations of the given seats in two concurrent IDM bulk
     * requests instead of two serial ones, populating the cache used by getSeatedUser()/getReservedClans().
     *
     * @param Seat[] $seats
     * @return array{0: (?User)[], 1: (?Clan)[]} seat id => owner map, seat id => clan reservation map
     */
    public function getSeatedUsersAndReservedClans(array $seats): array
    {
        $userUuids = array_filter(array_map(fn (Seat $seat) => $seat->getOwner()?->toString(), $seats));
        $clanUuids = array_filter(array_map(fn (Seat $seat) => $seat->getClanReservation()?->toString(), $seats));

        // both requests are dispatched together and their responses are awaited concurrently
        $this->manager->bulkMany([
            'users' => ['class' => User::class, 'ids' => $userUuids, 'cacheTtl' => self::IDM_CACHE_TTL],
            'clans' => ['class' => Clan::class, 'ids' => $clanUuids, 'cacheTtl' => self::IDM_CACHE_TTL],
        ]);

        $users = [];
        $clans = [];
        foreach ($seats as $seat) {
            $users[$seat->getId()] = $this->getSeatOwner($seat);
            $clans[$seat->getId()] = $this->getClanReservation($seat);
        }

        return [$users, $clans];
    }

    /**
     * @param Seat[] $seats
     * @return (?User)[]
     */
    public function getSeatedUser(array $seats): array
    {
        $uuids = array_map(fn (Seat $seat) => $seat->getOwner()?->toString(), $seats);
        $uuids = array_filter($uuids); // remove null from uuids
        // preload users
        $this->userRepo->findById($uuids, self::IDM_CACHE_TTL);

        $ret = [];
        foreach ($seats as $seat) {
            $ret[$seat->getId()] = $this->getSeatOwner($seat);
        }

        return $ret;
    }

    /**
     * @param Seat[] $seats
     * @return (?Clan)[]
     */
    public function getReservedClans(array $seats): array
    {
        $uuids = array_map(fn (Seat $seat) => $seat->getClanReservation()?->toString(), $seats);
        $uuids = array_filter($uuids); // remove null from uuids
        // preload clans
        $this->clanRepo->findById($uuids, self::IDM_CACHE_TTL);

        $ret = [];
        foreach ($seats as $seat) {
            $ret[$seat->getId()] = $this->getClanReservation($seat);
        }

        return $ret;
    }

    /**
     * Returns true if the provided User can still book a seat.
     */
    public function hasSeatEligibility(User|UuidInterface $user): bool
    {
        $countSeats = $this->getUserSeatCount($user);

        return $countSeats == 0 && ($this->ticketService->isUserRegistered($user)
                || $this->settingService->get('lan.seatmap.allow_booking_for_non_paid', false));
    }

    public function canBookSeat(Seat $seat, User|UuidInterface $user): bool
    {
        return $this->hasSeatEligibility($user) && $this->isSeatBookable($seat, $user);
    }

    public function isSeatOwner(Seat $seat, User|UuidInterface $user): bool
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $uuid->equals($seat->getOwner());
    }

    public function bookSeat(Seat $seat, User|UuidInterface $user): void
    {
        if ($this->canBookSeat($seat, $user)) {
            $uuid = $user instanceof User ? $user->getUuid() : $user;
            $seat->setOwner($uuid);
            $this->em->flush();
        } else {
            throw new GamerLifecycleException($user, "Seat {$seat->generateSeatName()} cannot be booked by user");
        }
    }

    public function unBookSeat(Seat $seat, User|UuidInterface $user): void
    {
        if ($this->isSeatOwner($seat, $user)) {
            $seat->setOwner(null);
            $this->em->flush();
        } else {
            throw new GamerLifecycleException($user, "Seat {$seat->generateSeatName()} cannot be unbooked, it does not belong to the user");
        }
    }

    public function getUserSeats(User|UuidInterface $user): array
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->seatRepository->findBy(['owner' => $uuid]);
    }

    public function getUserSeatCount(User|UuidInterface $user): int
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->seatRepository->count(['owner' => $uuid]);
    }

    public function isSeatBookable(Seat $seat, User $user): bool
    {
        if (!empty($seat->getClanReservation()) && !$this->userService->isUserInClan($user, $seat->getClanReservation())) {
            return false;
        }
        return match ($seat->getType()) {
            SeatKind::SEAT => empty($seat->getOwner()),
            SeatKind::LOCKED => $this->security->isGranted('ROLE_ADMIN_SEATMAP'),
            default => false,
        };
    }

    public function getSeatOwner(Seat $seat): ?User
    {
        return $seat->getOwner() ? $this->userRepo->findOneById($seat->getOwner()) : null;
    }

    public function getClanReservation(Seat $seat): ?Clan
    {
       return $seat->getClanReservation() ? $this->clanRepo->findOneById($seat->getClanReservation()) : null;
    }

    /**
     * @return UuidInterface[] all uuids of seat owners
     */
    public function getSeatOwners(): array
    {
        $seats = $this->seatRepository->findTakenSeats();
        $uuids = array_map(fn (Seat $s) => $s->getOwner(), $seats);
        return array_unique($uuids);
    }

    public function getDimension(): array
    {
        return $this->seatRepository->getMaxDimension();
    }
}
