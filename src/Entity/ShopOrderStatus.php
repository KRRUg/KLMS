<?php

namespace App\Entity;

enum ShopOrderStatus : int
{
    /** order created */
    case Created = 1;
    /** payment done */
    case Paid = 9;
    /** order cancelled */
    case Canceled = 99;
}
