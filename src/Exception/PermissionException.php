<?php


namespace App\Exception;

class PermissionException extends \RuntimeException
{
    public $missing_permission;

    public function __construct(string $permission)
    {
        parent::__construct("User missing permission {$permission}");
        $this->missing_permission = $permission;
    }
}