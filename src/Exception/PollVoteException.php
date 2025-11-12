<?php

namespace App\Exception;

use RuntimeException;

class PollVoteException extends RuntimeException
{
    public const CODE_ALREADY_VOTED = 'already_voted';

    public static function alreadyVoted(): self
    {
        return new self(self::CODE_ALREADY_VOTED);
    }
}
