<?php

namespace App\Exception;

use App\Entity\ShopOrder;
use RuntimeException;

class OrderLifecycleException extends RuntimeException
{
    public readonly ShopOrder $order;

    public function __construct(ShopOrder $order, $message = '')
    {
        parent::__construct($message);

        $this->order = $order;
    }
}
