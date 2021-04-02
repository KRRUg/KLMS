<?php

namespace App\Entity\EMail;

use App\Entity\User;
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

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
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

    public function printDataset(): string
    {
        $output = "";
        $dataset = $this->getDataArray();
        ksort($dataset);
        foreach ($dataset as $key => $value) {
            $output .= "$key: $value \n ";
        }
        return $output;
    }


    public function generateTestLinkHash()
    {
        return md5(rand());
    }


}