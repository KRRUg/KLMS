<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AccountNotConfirmedException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct('Your email address was not confirmed yet.');
    }
}
