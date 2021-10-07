<?php


namespace App\Service;


use App\Entity\Seat;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
use App\Repository\SeatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class SeatmapService
{
    private EntityManagerInterface $em;
    private SeatRepository $seatRepository;
    private GamerService $gamerService;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        GamerService $gamerService,
        SeatRepository $seatRepository,
        Security $security)
    {
        $this->em = $entityManager;
        $this->seatRepository = $seatRepository;
        $this->gamerService = $gamerService;
        $this->security = $security;
    }

    public function getSeatmap(): array
    {
        return $this->seatRepository->findAll();
    }

    /**
     * Returns true if the provided User can still book a seat
     */
    public function hasSeatEligibility(User $user): bool
    {
        $current_seat = $this->getGamerCurrentSeatCount($user);
        return $current_seat == 0 && $this->gamerService->gamerHasPaid($user);
    }

    public function canBookSeat(Seat $seat, User $user): bool
    {
        return $this->hasSeatEligibility($user) && $this->isSeatBookable($seat);
    }

    public function isSeatOwner(Seat $seat, User $user): bool
    {
        return $seat->getOwner() === $this->gamerService->getGamer($user);
    }

    public function bookSeat(Seat $seat, User $user)
    {
        if($this->canBookSeat($seat, $user)) {
            $seat->setOwner($this->gamerService->getGamer($user));
            $this->em->flush();
        } else {
            throw new GamerLifecycleException($user, "Seat {$seat->generateSeatName()} cannot be booked by user");
        }
    }

    public function unBookSeat(Seat $seat, User $user)
    {
        if($this->isSeatOwner($seat, $user)) {
            $seat->setOwner(null);
            $this->em->flush();
        } else {
            throw new GamerLifecycleException($user, "Seat {$seat->generateSeatName()} cannot be unbooked, it does not belong to the user");
        }
    }

    private function getGamerCurrentSeatCount(User $user): int
    {
        $userGamer = $this->gamerService->getGamer($user);

        return $this->seatRepository->count(['owner' => $userGamer]);
    }

    public function isSeatBookable(Seat $seat): bool
    {
        switch ($seat->getType()) {
            case 'seat':
                return empty($seat->getOwner());
            case 'locked':
                return $this->security->isGranted('ROLE_ADMIN_SEATMAP');
            default:
                return false;
        }
    }

    public function getSeatOwner(Seat $seat)
    {
        if ($seat->getOwner()) {
            return $this->gamerService->getUserFromGamer($seat->getOwner());
        } else {
            return null;
        }
    }
}