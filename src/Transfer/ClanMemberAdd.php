<?php

namespace App\Transfer;

use App\Model\ClanModel;
use App\Security\User;
use Symfony\Component\Validator\Constraints as Assert;

final class ClanMemberAdd
{
    /**
     * @Assert\Type(type="string")
     */
    public $joinPassword;

    /**
     * @Assert\All({
     *      @Assert\NotBlank,
     *      @Assert\Uuid(strict=false)
     * })
     */
    public $users = [];

    /**
     * @param User[] $userarray
     * @param string|null $joinPassword
     * @return static
     */
    public static function fromUsers(array $userarray, string $joinPassword = null): self
    {
        $transfer = new self();
        if(!empty($joinPassword)) {
            $transfer->joinPassword = $joinPassword;
        }

        foreach ($userarray as $user) {
            $transfer->users[] = $user->getUuid();
        }

        return $transfer;
    }
}
