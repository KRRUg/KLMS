<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\AccountStatusException;

class AccountNotConfirmedException extends AccountStatusException
{
    public function getMessageKey(): string
    {
        return 'Your email address was not confirmed yet.';
    }
}
