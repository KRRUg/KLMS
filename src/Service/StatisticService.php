<?php

namespace App\Service;

use App\Repository\SeatRepository;
use App\Repository\ShopOrderRepository;
use App\Repository\TicketRepository;

class StatisticService extends OptimalService
{
    private readonly SeatRepository $seatRepository;
    private readonly TicketRepository $ticketRepository;
    private readonly ShopOrderRepository $shopOrderRepository;

    public function __construct(
        SeatRepository      $seatRepository,
        TicketRepository    $ticketRepository,
        ShopOrderRepository $shopOrderRepository,
        SettingService      $settingService
    ) {
        parent::__construct($settingService);
        $this->seatRepository = $seatRepository;
        $this->ticketRepository = $ticketRepository;
        $this->shopOrderRepository = $shopOrderRepository;
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
            // TODO change names
            'gamer_registered' => $this->countOrderedTickets(),
            'gamer_payed' => $this->countRedeemedTickets(),
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

    public function countOrderedTickets(): int
    {
        return $this->ticketRepository->count([]) + $this->shopOrderRepository->countOrderedTickets();
    }

    public function countRedeemedTickets(): int
    {
        return $this->ticketRepository->countRedeemed();
    }
}
