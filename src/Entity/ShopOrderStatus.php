<?php

namespace App\Entity;

enum ShopOrderStatus : string
{
    /** order created */
    case Created = 'created';
    /** ongoing payment process */
    case PaymentPending = 'pending';
    /** payment done */
    case Paid = 'paid';
    /** order cancelled */
    case Canceled = 'canceled';
}
