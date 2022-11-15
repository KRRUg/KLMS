<?php

namespace App\Service;

use App\Repository\SeatRepository;
use App\Repository\UserGamerRepository;

class StatisticService extends OptimalService
{
    private SeatRepository $seatRepository;
    private UserGamerRepository $gamerRepository;

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
        switch ($key) {
            case 'seats_free':
                return $this->countSeatsFree();
            case 'seats_total':
                return $this->countSeatsTotal();
            case 'seats_taken':
                return $this->countSeatsTaken();
            case 'gamer_registered':
                return $this->countGamerRegistered();
            case 'gamer_payed':
                return $this->countGamerPayed();
            default:
                return '';
        }
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
