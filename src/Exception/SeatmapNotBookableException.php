<?php

namespace App\Exception;

use App\Entity\Seat;

class SeatmapNotBookableException extends \RuntimeException
{
    private string $seatLocation;

    public function __construct(Seat $seat, $message = "")
    {
        parent::__construct($message);
        $this->seatLocation = $seat->getSector() . '-' . $seat->getSeatNumber();
    }
}