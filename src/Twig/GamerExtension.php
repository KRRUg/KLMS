<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\GamerService;
use App\Entity\Seat;
use App\Service\SeatmapService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class GamerExtension extends AbstractExtension
{
    private GamerService $gamerService;
    private SeatmapService $seatmapService;

    public function __construct(GamerService $gamerService, SeatmapService $seatmapService)
    {
        $this->gamerService = $gamerService;
        $this->seatmapService = $seatmapService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('registered_gamer', [$this, 'gamerIsRegistered']),
            new TwigTest('paid_gamer', [$this, 'gamerIsPaid']),
            new TwigTest('seated_gamer', [$this, 'gamerIsSeated']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('seat', [$this, 'getSeat']),
        ];
    }

    public function gamerIsRegistered(User $user): bool
    {
        return $this->gamerService->gamerHasRegistered($user);
    }

    public function gamerIsPaid(User $user): bool
    {
        return $this->gamerService->gamerHasPaid($user);
    }

    public function gamerIsSeated(User $user): bool
    {
        return $this->seatmapService->getUserSeatCount($user) > 0;
    }

    public function getSeat(User $user): ?string
    {
        $seats = $this->seatmapService->getUserSeats($user);
        $names = array_map(function (Seat $seat) { return $seat->generateSeatName(); }, $seats);
        return implode(',', $names);
    }
}
