<?php

namespace App\Helper;

use App\Entity\User;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Mime\Address;

class EmailRecipient
{
    private UuidInterface $id;
    private string $emailAddress;
    private string $nickname;
    private string $firstname;
    private string $surname;

    private function __construct(UuidInterface $id,
                                string $emailAddress,
                                ?string $nickname,
                                ?string $firstname,
                                ?string $surname)
    {
        $this->id = $id;
        $this->emailAddress = $emailAddress;
        $this->nickname = $nickname ?? '';
        $this->firstname = $firstname ?? '';
        $this->surname = $surname ?? '';
    }

    public static function fromUser(User $user): ?self
    {
        if (empty($user->getUuid()) || empty($user->getEmail())) {
            return null;
        }
        return new self($user->getUuid(), $user->getEmail(), $user->getNickname(), $user->getFirstname(), $user->getSurname());
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->id;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getAddressObject(): Address
    {
        $name = $this->getFirstname() . ' ' . $this->getSurname();
        return new Address($this->emailAddress, $name);
    }

    public function getDataArray(): array
    {
        $dataset = [
            "id" => $this->id->toString(),
            "nickname" => $this->nickname,
            "firstname" => $this->firstname,
            "surname" => $this->surname,
            "email" => $this->emailAddress,
        ];
        return array_change_key_case($dataset, CASE_LOWER);
    }
}