<?php

namespace App\Twig;

use App\Entity\Seat;
use App\Entity\User;
use App\Service\SeatmapService;
use App\Service\TicketService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class GamerExtension extends AbstractExtension
{
    private readonly TicketService $ticketService;
    private readonly SeatmapService $seatmapService;

    public function __construct(TicketService $ticketService, SeatmapService $seatmapService)
    {
        $this->ticketService = $ticketService;
        $this->seatmapService = $seatmapService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTests(): array
    {
        return [
            new TwigTest('registered_gamer', $this->gamerIsRegistered(...)),
            new TwigTest('seated_gamer', $this->gamerIsSeated(...)),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('seat', $this->getSeat(...)),
        ];
    }

    public function gamerIsRegistered(User $user): bool
    {
        return $this->ticketService->isUserRegistered($user);
    }

    public function gamerIsSeated(User $user): bool
    {
        return $this->seatmapService->getUserSeatCount($user) > 0;
    }

    public function getSeat(User $user): string
    {
        $seats = $this->seatmapService->getUserSeats($user);
        $names = array_map(fn (Seat $seat) => $seat->generateSeatName(), $seats);

        return implode(',', $names);
    }
}
