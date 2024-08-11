<?php

namespace App\Entity;

enum SeatKind: string
{
    case SEAT = 'seat';
    case LOCKED = 'locked';
    case INFO = 'info';
}