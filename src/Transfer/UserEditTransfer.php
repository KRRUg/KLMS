<?php

namespace App\Transfer;

use DateTime;
use Symfony\Component\Validator\Constraints as Assert;
use App\Security\User;

final class UserEditTransfer
{

    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max="250")
     * @var string
     */
    public $email;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="250")
     * @var string
     */
    public $nickname;

    /**
     * @var string
     */
    public $birthdate;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $firstname;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $surname;

    /**
     * @var integer
     */
    public $postcode;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $city;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $street;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $country;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $phone;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $gender;

    /**
     * @Assert\NotBlank()
     * @var bool
     */
    public $emailConfirmed;

    /**
     * @Assert\Url()
     * @Assert\Length(max="250")
     * @var string
     */
    public $website;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $steamAccount;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $hardware;

    /**
     * @Assert\Length(max="250")
     * @Assert\NotBlank()
     * @var bool
     */
    public $infoMails;

    /**
     * @Assert\Length(max="250")
     * @var string
     */
    public $statements;

    /**
     * @var int
     */
    public $status;

    public static function fromUser(User $user): self
    {
        $transfer = new self();
        $transfer->email = $user->getEmail();
        $transfer->nickname = $user->getNickname();
        if(null !== $user->getBirthdate()) {
            $transfer->birthdate = $user->getBirthdate()->format(DateTime::ATOM);
        }
        $transfer->firstname = $user->getFirstname();
        $transfer->surname = $user->getSurname();
        $transfer->postcode = $user->getPostcode();
        $transfer->city = $user->getCity();
        $transfer->street = $user->getStreet();
        $transfer->country = $user->getCountry();
        $transfer->phone = $user->getPhone();
        $transfer->gender = $user->getGender();
        $transfer->emailConfirmed = $user->getEmailConfirmed();
        $transfer->website = $user->getWebsite();
        $transfer->steamAccount = $user->getSteamAccount();
        $transfer->hardware = $user->getHardware();
        $transfer->infoMails = $user->getInfoMails();
        $transfer->statements = $user->getStatements();
        $transfer->status = $user->getStatus();


        return $transfer;
    }
}