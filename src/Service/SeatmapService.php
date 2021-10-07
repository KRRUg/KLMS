<?php


namespace App\Service;


use App\Entity\Seat;
use App\Entity\User;
use App\Exception\SeatmapAlreadyOwnedException;
use App\Exception\SeatmapNotBookableException;
use App\Repository\SeatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class SeatmapService
{
    private bool $adminMode;
    private EntityManagerInterface $em;
    private SeatRepository $seatRepository;
    private GamerService $gamerService;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        GamerService $gamerService,
        SeatRepository $seatRepository,
        Security $security
    )
    {
        $this->adminMode = false;
        $this->em = $entityManager;
        $this->seatRepository = $seatRepository;
        $this->gamerService = $gamerService;
        $this->security = $security;
    }

    public function getSeatmap(): array
    {
        return $this->seatRepository->findAll();
    }

    public function getSeatName(Seat $seat): string
    {
        if ($seat->getName())
            return $seat->getName();
        else
            return "{$seat->getSector()}-{$seat->getSeatNumber()}";
    }

    /**
     * Returns true if the provided User can still book Seats (under his paid limit)
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
        if($this->canBookSeat($seat, $user) || $this->adminMode) {
            $seat->setOwner($this->gamerService->getGamer($user));
            $this->em->flush();
        }
    }

    public function unBookSeat(Seat $seat, User $user)
    {
        if($this->isSeatOwner($seat, $user) || $this->adminMode) {
            $seat->setOwner(null);
            $this->em->flush();
        }
    }

    public function setAdminMode($adminMode): void
    {
        $this->adminMode = $adminMode;
    }

    private function getGamerCurrentSeatCount(User $user): int
    {
        $userGamer = $this->gamerService->getGamer($user);

        return $this->seatRepository->count(['owner' => $userGamer]);
    }

    private function isSeatBookable(Seat $seat): bool
    {
        switch ($seat->getType()) {
            case 'seat':
                    $this->isSeatOwned($seat);
                    return true;
            case 'locked':
                if ($this->security->isGranted('ROLE_ADMIN_SEATMAP') && $this->adminMode) {
                    return true;
                } else {
                    throw new AccessDeniedException("You aren't allowed to assign locked Seats");
                }
            default:
                throw new SeatmapNotBookableException($seat);
        }
    }

    private function isSeatOwned(Seat $seat): void
    {
        if($seat->getOwner()){
            throw new SeatmapAlreadyOwnedException($seat);
        }
    }
}