<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\LoginUser;

trait LoginUserTrait
{
    protected function getLoginUser(): ?LoginUser
    {
        $user = parent::getUser();

        return $user instanceof LoginUser ? $user : null;
    }

    protected function getDomainUser(): ?User
    {
        $loginUser = $this->getLoginUser();

        return $loginUser?->getUser();
    }

    protected function requireLoginUser(): LoginUser
    {
        $loginUser = $this->getLoginUser();
        if (!$loginUser) {
            throw $this->createAccessDeniedException();
        }

        return $loginUser;
    }

    protected function requireDomainUser(): User
    {
        $user = $this->getDomainUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
