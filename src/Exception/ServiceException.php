<?php

namespace App\Exception;

use RuntimeException;

class ServiceException extends RuntimeException
{
    // Types of causes
    final public const CAUSE_EMPTY = 'is_empty';
    final public const CAUSE_IN_USE = 'in_use';
    final public const CAUSE_DONT_EXIST = 'dont_exists';
    final public const CAUSE_EXIST = 'already_exists';
    final public const CAUSE_INVALID = 'invalid';

    private readonly string $cause;

    public function __construct($cause, $message = '')
    {
        $this->cause = $cause;
        parent::__construct($message);
    }

    public function getCause(): string
    {
        return $this->cause;
    }
}
