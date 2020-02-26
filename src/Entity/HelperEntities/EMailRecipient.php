<?php


namespace App\Entity\HelperEntities;


use Symfony\Component\Mime\Address;

class EMailRecipient
{
    private $emailAddress;
    private $name;

    public function __construct($name, $emailAddress)
    {
        $this->name = $name;
        $this->emailAddress = $emailAddress;
    }

    public function getAddressObject()
    {
        return new Address($this->emailAddress, $this->name);
    }

    public function getDataArray()
    {
        $dataset = [
            "name" => $this->name,
            "email" => $this->emailAddress
        ];
        return array_change_key_case($dataset, CASE_LOWER);
    }


}