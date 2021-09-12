<?php


namespace App\Service;


use App\Entity\Seat;
use App\Entity\User;
use App\Exception\GamerLifecycleException;
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
        //foo
    }

    public function getSeatName(Seat $seat): string
    {
        $name = "{$seat->getSector()}-{$seat->getSeatNumber()}";
        return $name;
    }

    /**
     * Returns true if the provided User can still book Seats (under his paid limit)
     */
    public function hasSeatEligibility(User $user): bool
    {
        $current_seat = $this->_getGamerCurrentSeatCount($user);
        $paid_seat = $this->gamerService->getGamerPaidSeatCount($user);
        if(is_int($current_seat) && is_int($paid_seat)) {
            if ($current_seat < $paid_seat) {
                return true;
            }

            return false;
        } else {
            throw new GamerLifecycleException($user, 'Could not verify Seat eligibility!');
        }
    }

    public function canBookSeat(Seat $seat, User $user): bool
    {
        if($this->hasSeatEligibility($user)) {
            if($this->_isSeatBookable($seat)) {
                return true;
            }
        }
        return false;
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

    private function _getGamerCurrentSeatCount(User $user): int
    {
        $userGamer = $this->gamerService->getGamer($user);

        return $this->seatRepository->count(['owner' => $userGamer]);
    }

    private function _isSeatBookable(Seat $seat): bool
    {
        switch ($seat->getType()) {
            case 'seat':
                    $this->_isSeatOwned($seat);
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

    private function _isSeatOwned(Seat $seat): void
    {
        if($seat->getOwner()){
            throw new SeatmapAlreadyOwnedException($seat);
        }
    }
}