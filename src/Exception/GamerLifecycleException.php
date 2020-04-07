<?php


namespace App\Exception;

use App\Security\User;

class GamerLifecycleException extends \RuntimeException
{
    private $gamerName;

    public function __construct(User $gamer, $message = "")
    {
        parent::__construct($message);
        $this->gamerName = $gamer->getUsername();
    }
}