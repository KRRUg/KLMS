<?php

namespace App\Service;

use App\Entity\ShopOrderStatus;
use App\Repository\SeatRepository;
use App\Repository\SettingRepository;
use App\Repository\ShopOrderPositionRepository;
use App\Repository\TicketRepository;

class StatisticService extends OptimalService
{
    private readonly SeatRepository $seatRepository;
    private readonly TicketRepository $ticketRepository;
    private readonly ShopOrderPositionRepository $shopOrderPositionRepository;
    private readonly SettingRepository $settingRepository;

    public function __construct(
        SeatRepository              $seatRepository,
        TicketRepository            $ticketRepository,
        ShopOrderPositionRepository $shopOrderPositionRepository,
        SettingRepository           $settingRepository,
        SettingService              $settingService
    ) {
        parent::__construct($settingService);
        $this->seatRepository = $seatRepository;
        $this->ticketRepository = $ticketRepository;
        $this->shopOrderPositionRepository = $shopOrderPositionRepository;
        $this->settingRepository = $settingRepository;
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
            'seats_consumed' => $this->countConsumedSeats(),
            'tickets_total' => $this->getTicketsTotal(),
            'tickets_ordered' => $this->countOrderedTickets(),
            'tickets_sold' => $this->countSoldTickets(),
            default => '',
        };
    }

    public function countSeatsTotal(): int
    {
        return $this->seatRepository->countSeatsTotal();
    }

    public function countSeatsFree(): int
    {
        return $this->seatRepository->countFreeSeats() - $this->countConsumedSeats();
    }

    public function countSeatsTaken(): int
    {
        return $this->seatRepository->countTakenSeats();
    }

    public function countSeatsLocked(): int
    {
        return $this->seatRepository->countLockedSeats() + $this->seatRepository->countClanReservedSeats();
    }

    public function countConsumedSeats(): int
    {
        return $this->shopOrderPositionRepository->countConsumedSeats();
    }

    public function getTicketsTotal(): int
    {
        $setting = $this->settingRepository->findByKey('lan.stats.tickets_total');
        return $setting ? (int) $setting->getText() : 0;
    }

    public function countOrderedTickets(): int
    {
        // Bestellte Tickets (nicht storniert) + je 1 Ticket pro Addon-Einheit mit consumesSeats (nicht storniert)
        return $this->ticketRepository->countRedeemedWithoutOrder()
            + $this->shopOrderPositionRepository->countTicketsNotCancelled()
            + $this->shopOrderPositionRepository->countConsumedTickets(ShopOrderStatus::STATUS_NOT_DEAD);
    }

    public function countSoldTickets(): int
    {
        // Bezahlte Tickets + je 1 Ticket pro Addon-Einheit mit consumesSeats aus bezahlten Bestellungen
        return $this->ticketRepository->countRedeemed()
            + $this->ticketRepository->countFromTicket()
            + $this->shopOrderPositionRepository->countConsumedTickets(ShopOrderStatus::STATUS_ACTIVE);
    }
}
