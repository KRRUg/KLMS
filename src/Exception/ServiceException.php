<?php

namespace App\Exception;

use RuntimeException;

class ServiceException extends RuntimeException
{
    // Types of causes
    public const CAUSE_EMPTY = 'is_empty';
    public const CAUSE_IN_USE = 'in_use';
    public const CAUSE_DONT_EXIST = 'dont_exists';
    public const CAUSE_EXIST = 'already_exists';
    public const CAUSE_INVALID = 'invalid';

    private string $cause;

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
