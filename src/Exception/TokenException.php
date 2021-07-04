<?php

namespace App\Exception;

class TokenException extends \RuntimeException
{
    const CAUSE_INVALID = 1;
    const CAUSE_FORMAT_ERROR = 2;
    const CAUSE_NOT_FOUND = 3;
    const CAUSE_EXPIRED = 4;
    const CAUSE_THROTTLE = 5;

    private int $cause;

    public function __construct($cause, $message = "")
    {
        $this->cause = $cause;
        parent::__construct($message);
    }
}