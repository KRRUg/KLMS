<?php


namespace App\Entity\HelperEntities;


use Symfony\Component\Mime\Address;

class EMailRecipient
{
    private $id;
    private $emailAddress;
    private $name;


    public function __construct($id, $name, $emailAddress)
    {
        $this->id = $id;
        $this->name = $name;
        $this->emailAddress = $emailAddress;
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
            "email" => $this->emailAddress
        ];
        return array_change_key_case($dataset, CASE_LOWER);
    }


    public function generateTestLinkHash()
    {
        return md5(rand());
    }


}