<?php

namespace App\Service;

use App\Entity\Seat;
use App\Entity\SeatKind;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Form\SeatType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\SeatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class SeatmapService
{
    private readonly EntityManagerInterface $em;
    private readonly SeatRepository $seatRepository;
    private readonly TicketService $ticketService;
    private readonly Security $security;
    private readonly IdmRepository $userRepo;
    private readonly SettingService $settingService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SeatRepository $seatRepository,
        IdmManager $manager,
        Security $security,
        TicketService $ticketService,
        SettingService $settingService)
    {
        $this->em = $entityManager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->seatRepository = $seatRepository;
        $this->security = $security;
        $this->ticketService = $ticketService;
        $this->settingService = $settingService;
    }

    public function getSeatmap(): array
    {
        return $this->seatRepository->findAll();
    }

    /**
     * @param Seat[] $seats
     * @return array
     */
    public function getSeatedUser(array $seats): array
    {
        // preload users
        $uuids = array_map(fn (Seat $seat) => $seat->getOwner()?->toString(), $seats);
        $uuids = array_filter($uuids); // remove null from uuids
        $this->userRepo->findById($uuids);

        $ret = [];
        foreach ($seats as $seat) {
            $ret[$seat->getId()] = $this->getSeatOwner($seat);
        }

        return $ret;
    }

    /**
     * Returns true if the provided User can still book a seat.
     */
    public function hasSeatEligibility(User $user): bool
    {
        $countSeats = $this->getUserSeatCount($user);

        return $countSeats == 0 && ($this->ticketService->isUserRegistered($user)
                || $this->settingService->isSet('lan.seatmap.allow_booking_for_non_paid') === true);
    }

    public function canBookSeat(Seat $seat, User $user): bool
    {
        return $this->hasSeatEligibility($user) && $this->isSeatBookable($seat);
    }

    public function isSeatOwner(Seat $seat, User $user): bool
    {
        return $user->getUuid()->equals($seat->getOwner());
    }

    public function bookSeat(Seat $seat, User $user): void
    {
        if ($this->canBookSeat($seat, $user)) {
            $seat->setOwner($user->getUuid());
            $this->em->flush();
        } else {
            throw new GamerLifecycleException($user, "Seat {$seat->generateSeatName()} cannot be booked by user");
        }
    }

    public function unBookSeat(Seat $seat, User $user): void
    {
        if ($this->isSeatOwner($seat, $user)) {
            $seat->setOwner(null);
            $this->em->flush();
        } else {
            throw new GamerLifecycleException($user, "Seat {$seat->generateSeatName()} cannot be unbooked, it does not belong to the user");
        }
    }

    public function getUserSeats(User $user): array
    {
        return $this->seatRepository->findBy(['owner' => $user->getUuid()]);
    }

    public function getUserSeatCount(User $user): int
    {
        return $this->seatRepository->count(['owner' => $user->getUuid()]);
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

    public function getDimension(): array
    {
        return $this->seatRepository->getMaxDimension();
    }
}
