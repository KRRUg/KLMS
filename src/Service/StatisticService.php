<?php

namespace App\Service;

use App\Repository\SeatRepository;
use App\Repository\ShopOrderPositionRepository;
use App\Repository\TicketRepository;

class StatisticService extends OptimalService
{
    private readonly SeatRepository $seatRepository;
    private readonly TicketRepository $ticketRepository;
    private readonly ShopOrderPositionRepository $shopOrderPositionRepository;

    public function __construct(
        SeatRepository              $seatRepository,
        TicketRepository            $ticketRepository,
        ShopOrderPositionRepository $shopOrderPositionRepository,
        SettingService              $settingService
    ) {
        parent::__construct($settingService);
        $this->seatRepository = $seatRepository;
        $this->ticketRepository = $ticketRepository;
        $this->shopOrderPositionRepository = $shopOrderPositionRepository;
    }

    protected static function getSettingKey(): string
    {
        return 'lan.stats.show';
    }

    public function get(string $key): string
    {
        return match ($key) {
            'seats_free' => $this->countSeatsFree(),
            'seats_total' => $this->countSeatsTotal(),
            'seats_taken' => $this->countSeatsTaken(),
            'seats_locked' => $this->countSeatsLocked(),
            'tickets_ordered' => $this->countOrderedTickets(),
            'tickets_sold' => $this->countSoldTickets(),
            'tickets_redeemed' => $this->countRedeemedTickets(),
            default => '',
        };
    }

    public function countSeatsTotal(): int
    {
        return $this->seatRepository->countSeatsTotal();
    }

    public function countSeatsFree(): int
    {
        return $this->seatRepository->countFreeSeats();
    }

    public function countSeatsTaken(): int
    {
        return $this->seatRepository->countTakenSeats();
    }

    public function countSeatsLocked(): int
    {
        return $this->seatRepository->countLockedSeats() + $this->seatRepository->countClanReservedSeats();
    }

    public function countOrderedTickets(): int
    {
        return $this->ticketRepository->count([]) + $this->shopOrderPositionRepository->countOrderedTickets();
    }

    public function countSoldTickets(): int
    {
        return $this->ticketRepository->count([]);
    }

    public function countRedeemedTickets(): int
    {
        return $this->ticketRepository->countRedeemed();
    }
}
