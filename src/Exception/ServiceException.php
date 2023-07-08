<?php

namespace App\Exception;

use RuntimeException;

class ServiceException extends RuntimeException
{
    // Types of causes
    final public const CAUSE_EMPTY = 1;
    final public const CAUSE_IN_USE = 2;
    final public const CAUSE_DONT_EXIST = 3;
    final public const CAUSE_EXIST = 4;
    final public const CAUSE_INVALID = 5;
    final public const CAUSE_INCONSISTENT = 6;
    final public const CAUSE_FULL = 7;
    final public const CAUSE_FORBIDDEN = 8;

    public function __construct(int $cause, string $message = '')
    {
        parent::__construct($message, $cause);
    }

    public function getCause(): int
    {
        return $this->getCode();
    }
}
