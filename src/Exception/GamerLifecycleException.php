<?php

namespace App\Exception;

use App\Entity\User;

class GamerLifecycleException extends \RuntimeException
{
    private string $gamerName;

    public function __construct(User $gamer, $message = "")
    {
        parent::__construct($message);
        $this->gamerName = $gamer->getNickname();
    }
}