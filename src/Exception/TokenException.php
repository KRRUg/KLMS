<?php

namespace App\Exception;

use RuntimeException;

class TokenException extends RuntimeException
{
    final public const CAUSE_INVALID = 1;
    final public const CAUSE_FORMAT_ERROR = 2;
    final public const CAUSE_NOT_FOUND = 3;
    final public const CAUSE_EXPIRED = 4;
    final public const CAUSE_THROTTLE = 5;

    private readonly int $cause;

    public function __construct($cause, $message = '')
    {
        $this->cause = $cause;
        parent::__construct($message);
    }
}
