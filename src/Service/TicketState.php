<?php

namespace App\Service;

enum TicketState
{
    case NEW;
    case REDEEMED;
    case PUNCHED;
}
