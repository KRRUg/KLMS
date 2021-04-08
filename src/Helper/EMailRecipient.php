<?php

namespace App\Helper;

use App\Entity\User;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Mime\Address;

class EMailRecipient
{
    private $id;
    private $emailAddress;
    private $name;
    private $nickname;

    public function __construct(User $user)
    {
        $this->id = $user->getUuid();
        $this->name = $user->getFirstname() . ' ' . $user->getSurname();
        $this->emailAddress = $user->getEmail();
        $this->nickname = $user->getNickname();
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    public function getAddressObject()
    {
        return new Address($this->emailAddress, $this->name);
    }

    public function getDataArray()
    {
        $dataset = [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->emailAddress,
            "nickname" => $this->nickname
        ];
        return array_change_key_case($dataset, CASE_LOWER);
    }
}