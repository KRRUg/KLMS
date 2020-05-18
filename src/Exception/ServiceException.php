<?php


namespace App\Exception;


class ServiceException extends \RuntimeException
{
    // Types of causes
    const CAUSE_IN_USE = 'in_use';
    const CAUSE_DONT_EXIST = 'already_exists';


    private $cause;

    public function __construct($cause, $message = "")
    {
        $this->cause = $cause;
        parent::__construct($message);
    }

    public function getCause() : string
    {
        return $this->cause;
    }
}