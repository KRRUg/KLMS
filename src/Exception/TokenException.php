<?php

namespace App\Exception;

use RuntimeException;

class TokenException extends RuntimeException
{
    public const CAUSE_INVALID = 1;
    public const CAUSE_FORMAT_ERROR = 2;
    public const CAUSE_NOT_FOUND = 3;
    public const CAUSE_EXPIRED = 4;
    public const CAUSE_THROTTLE = 5;

    private int $cause;

    public function __construct($cause, $message = '')
    {
        $this->cause = $cause;
        parent::__construct($message);
    }
}
