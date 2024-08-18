<?php

namespace App\Entity;

enum SeatOrientation: string
{
    case NORTH = 'n';
    case EAST = 'e';
    case SOUTH = 's';
    case WEST = 'w';
}
