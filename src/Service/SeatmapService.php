<?php

namespace App\Service;

use App\Entity\Seat;
use App\Entity\SeatKind;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\SeatRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\Security;

class SeatmapService
{
    private readonly EntityManagerInterface $em;
    private readonly SeatRepository $seatRepository;
    private readonly TicketService $ticketService;
    private readonly Security $security;
    private readonly IdmRepository $userRepo;
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
        $this->userRepo = $manager->getRepository(User::class);
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
     * @param Seat[] $seats
     * @return (?User)[]
     */
    public function getSeatedUser(array $seats): array
    {
        $uuids = array_map(fn (Seat $seat) => $seat->getOwner()?->toString(), $seats);
        $uuids = array_filter($uuids); // remove null from uuids
        // preload users
        $this->userRepo->findById($uuids);
        // preload clans
        $this->userService->getClansByUsers($uuids);

        $ret = [];
        foreach ($seats as $seat) {
            $ret[$seat->getId()] = $this->getSeatOwner($seat);
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
        return $this->hasSeatEligibility($user) && $this->isSeatBookable($seat);
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

    public function isSeatBookable(Seat $seat): bool
    {
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
