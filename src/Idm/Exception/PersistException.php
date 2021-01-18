<?php


namespace App\Idm\Exception;


class PersistException extends \RuntimeException
{
    public const REASON_UNKNOWN = 1;
    public const REASON_INVALID = 2;
    public const REASON_NON_UNIQUE = 3;
    public const REASON_NOT_FOUND = 4;

    protected object $entity;

    /**
     * PersistException constructor.
     */
    public function __construct(object $entity, $code = self::REASON_UNKNOWN, $message = "")
    {
        parent::__construct($message, $code);
        $this->entity = $entity;
    }

    /**
     * @return object
     */
    public function getEntity(): object
    {
        return $this->entity;
    }
}