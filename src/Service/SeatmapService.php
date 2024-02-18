<?php

namespace App\Service;

use App\Entity\Seat;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\SeatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class SeatmapService implements WipeInterface
{
    private readonly EntityManagerInterface $em;
    private readonly SeatRepository $seatRepository;
    private readonly GamerService $gamerService;
    private readonly Security $security;
    private readonly IdmRepository $userRepo;
    private readonly SettingService $settingService;

    public function __construct(
        EntityManagerInterface $entityManager,
        GamerService $gamerService,
        SeatRepository $seatRepository,
        IdmManager $manager,
        Security $security,
        SettingService $settingService)
    {
        $this->em = $entityManager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->seatRepository = $seatRepository;
        $this->gamerService = $gamerService;
        $this->security = $security;
        $this->settingService = $settingService;
    }

    public function getSeatmap(): array
    {
        return $this->seatRepository->findAll();
    }

    public function getSeatedUser(array $seats): array
    {
        $uuids = array_map(fn (Seat $seat) => $seat->getOwner() ? $seat->getOwner()->getUuid()->toString() : null, $seats);
        $uuids = array_filter($uuids); // remove null from uuids
        $users = $this->userRepo->findById($uuids);

        $uuids = array_map(fn (User $u) => $u->getUuid()->toString(), $users);
        $users = array_combine($uuids, $users);

        $ret = [];
        foreach ($seats as $seat) {
            $ret[$seat->getId()] = $seat->getOwner() ?
                $users[$seat->getOwner()->getUuid()->toString()] :
                null;
        }

        return $ret;
    }

    /**
     * Returns true if the provided User can still book a seat.
     */
    public function hasSeatEligibility(User $user): bool
    {
        $current_seat = $this->getUserSeatCount($user);

        return $current_seat == 0 && ($this->gamerService->gamerHasPaid($user) || $this->settingService->isSet('lan.seatmap.allow_booking_for_non_paid') === true);
    }

    public function canBookSeat(Seat $seat, User $user): bool
    {
        return $this->hasSeatEligibility($user) && $this->isSeatBookable($seat);
    }

    public function isSeatOwner(Seat $seat, User $user): bool
    {
        $owner = $seat->getOwner();
        if (empty($owner)) {
            return false;
        }

        return $owner === $this->gamerService->getGamer($user);
    }

    public function bookSeat(Seat $seat, User $user): void
    {
        if ($this->canBookSeat($seat, $user)) {
            $seat->setOwner($this->gamerService->getGamer($user));
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
        $userGamer = $this->gamerService->getGamer($user);

        return $this->seatRepository->findBy(['owner' => $userGamer]);
    }

    public function getUserSeatCount(User $user): int
    {
        $userGamer = $this->gamerService->getGamer($user);

        return $this->seatRepository->count(['owner' => $userGamer]);
    }

    public function isSeatBookable(Seat $seat): bool
    {
        return match ($seat->getType()) {
            'seat' => empty($seat->getOwner()),
            'locked' => $this->security->isGranted('ROLE_ADMIN_SEATMAP'),
            default => false,
        };
    }

    public function getSeatOwner(Seat $seat): ?User
    {
        if ($seat->getOwner()) {
            return $this->gamerService->getUserFromGamer($seat->getOwner());
        } else {
            return null;
        }
    }

    public function reset(): void
    {
        foreach ($this->seatRepository->findAll() as $seat) {
            $this->em->remove($seat);
        }
        $this->em->flush();
    }

    public function resetBefore(): array
    {
        return [GamerService::class];
    }
}
