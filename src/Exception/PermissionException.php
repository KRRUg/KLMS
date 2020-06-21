<?php


namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PermissionException extends AccessDeniedException
{
    public $missing_permission;

    public function __construct(string $permission)
    {
        parent::__construct("User missing permission {$permission}");
        $this->missing_permission = $permission;
    }
}