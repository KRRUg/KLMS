<?php

namespace App\Transfer;

use App\Security\User;
use Symfony\Component\Validator\Constraints as Assert;

final class ClanMemberRemove
{
    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     */
    public $users = [];

    /**
     * @Assert\Type(type="boolean")
     */
    public $strict;

    /**
     * @param User[] $userarray
     * @param bool $strict
     * @return static
     */
    public static function fromUsers(array $userarray, bool $strict = true): self
    {
        $transfer = new self();
        $transfer->strict = $strict;

        foreach ($userarray as $user) {
            $transfer->users[] = $user->getUuid();
        }

        return $transfer;
    }
}
