<?php

namespace App\Idm\Exception;

use RuntimeException;

class PersistException extends RuntimeException
{
    public const REASON_UNKNOWN = 1;
    public const REASON_INVALID = 2;
    public const REASON_NON_UNIQUE = 3;
    public const REASON_NOT_FOUND = 4;
    public const REASON_IDM_ISSUE = 5;

    protected ?object $entity;
    protected string $property;

    /**
     * PersistException constructor.
     */
    public function __construct(?object $entity, $code = self::REASON_UNKNOWN, $message = '')
    {
        parent::__construct($message, $code);
        $this->entity = $entity;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
