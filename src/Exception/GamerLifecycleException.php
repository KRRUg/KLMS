<?php

namespace App\Exception;

use App\Entity\User;
use RuntimeException;

class GamerLifecycleException extends RuntimeException
{
    private readonly string $gamerName;

    public function __construct(User $gamer, $message = '')
    {
        parent::__construct($message);
        $this->gamerName = $gamer->getNickname();
    }
}
