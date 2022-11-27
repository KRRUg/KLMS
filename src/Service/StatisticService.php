<?php

namespace App\Service;

use App\Repository\SeatRepository;
use App\Repository\UserGamerRepository;

class StatisticService extends OptimalService
{
    private readonly SeatRepository $seatRepository;
    private readonly UserGamerRepository $gamerRepository;

    public function __construct(
        SeatRepository $seatRepository,
        UserGamerRepository $gamerRepository,
        SettingService $settingService
    ) {
        parent::__construct($settingService);
        $this->seatRepository = $seatRepository;
        $this->gamerRepository = $gamerRepository;
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
            'gamer_registered' => $this->countGamerRegistered(),
            'gamer_payed' => $this->countGamerPayed(),
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

    public function countGamerRegistered(): int
    {
        return $this->gamerRepository->countByState(true, null, null);
    }

    public function countGamerPayed(): int
    {
        return $this->gamerRepository->countByState(null, true, null);
    }
}
