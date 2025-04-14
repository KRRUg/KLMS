<?php

namespace App\Entity;

use App\Idm\Annotation as Idm;
use App\Idm\Collection;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Idm\Entity(path: '/users', authorize: true, bulk: true)]
class User
{
    private ?int $id = null;

    #[Assert\Uuid(strict: false)]
    #[Groups(['read'])]
    private ?UuidInterface $uuid = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 300, maxMessage: 'The email cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $email = null;

    #[Groups(['read', 'write'])]
    private ?bool $emailConfirmed = null;

    #[Groups(['read', 'write'])]
    private ?bool $infoMails = null;

    #[Assert\Length(min: 6, max: 128, minMessage: 'The password must be at least {{ limit }} characters long', maxMessage: 'The password cannot be longer than {{ limit }} characters')]
    #[Groups(['write'])]
    private ?string $password = null;

    #[Assert\Length(min: 1, max: 64, minMessage: 'The nickname must be at least {{ limit }} characters long', maxMessage: 'The nickname cannot be longer than {{ limit }} characters')]
    #[Assert\NotBlank]
    #[Groups(['read', 'write'])]
    private ?string $nickname = null;

    #[Groups(['read', 'write'])]
    #[Assert\Length(max: 250, maxMessage: 'The firstname cannot be longer than {{ limit }} characters')]
    private ?string $firstname = null;

    #[Assert\Length(max: 250, maxMessage: 'The surname cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $surname = null;

    #[Groups(['read', 'write'])]
    private ?DateTimeInterface $birthdate = null;

    #[Assert\Choice(['m', 'f', 'x'])]
    #[Groups(['read', 'write'])]
    private ?string $gender = null;

    #[Groups(['read', 'write'])]
    private ?bool $personalDataConfirmed = null;

    #[Groups(['read', 'write'])]
    private ?bool $personalDataLocked = null;

    #[Groups(['read'])]
    private ?bool $isSuperadmin = null;

    #[Assert\Length(min: 1, max: 10, minMessage: 'The postcode must be at least {{ limit }} characters long', maxMessage: 'The postcode cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $postcode = null;

    #[Assert\Length(max: 250, maxMessage: 'The city cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $city = null;

    #[Assert\Length(max: 250, maxMessage: 'The street cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $street = null;

    #[Assert\Country]
    #[Groups(['read', 'write'])]
    private ?string $country = null;

    #[Groups(['read', 'write'])]
    #[Assert\Length(max: 250, maxMessage: 'The phone number cannot be longer than {{ limit }} characters')]
    #[Assert\Regex('/^[+]?\d([ \/()]?\d)*$/', message: 'Invalid phone number format.')]
    private ?string $phone = null;

    #[Assert\Url]
    #[Assert\Length(max: 250, maxMessage: 'The website url cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $website = null;

    #[Assert\Length(max: 250, maxMessage: 'The steam account cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $steamAccount = null;

    #[Assert\Length(max: 250, maxMessage: 'The battle.net account cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $battlenetAccount = null;

    #[Groups(['read'])]
    private ?DateTimeInterface $registeredAt = null;

    #[Groups(['read'])]
    private ?DateTimeInterface $modifiedAt = null;

    #[Assert\Length(max: 4000, maxMessage: 'The hardware description cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $hardware = null;

    #[Assert\Length(max: 4000, maxMessage: 'The statement cannot be longer than {{ limit }} characters')]
    #[Groups(['read', 'write'])]
    private ?string $statements = null;

    #[Idm\Collection(class: Clan::class)]
    #[Groups(['read'])]
    private Collection|array $clans = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): User
    {
        $this->id = $id;

        return $this;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid): User
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getBirthdate(): ?DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getPersonalDataConfirmed(): ?bool
    {
        return $this->personalDataConfirmed;
    }

    public function setPersonalDataConfirmed(?bool $personalDataConfirmed): User
    {
        $this->personalDataConfirmed = $personalDataConfirmed;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(?string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getEmailConfirmed(): ?bool
    {
        return $this->emailConfirmed;
    }

    public function setEmailConfirmed(?bool $emailConfirmed): self
    {
        $this->emailConfirmed = $emailConfirmed;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getPersonalDataLocked(): ?bool
    {
        return $this->personalDataLocked;
    }

    public function setPersonalDataLocked(?bool $personalDataLocked): User
    {
        $this->personalDataLocked = $personalDataLocked;

        return $this;
    }

    public function getIsSuperadmin(): ?bool
    {
        return $this->isSuperadmin;
    }

    public function setIsSuperadmin(?bool $isSuperadmin): self
    {
        $this->isSuperadmin = $isSuperadmin;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getSteamAccount(): ?string
    {
        return $this->steamAccount;
    }

    public function setSteamAccount(?string $steamAccount): self
    {
        $this->steamAccount = $steamAccount;

        return $this;
    }

    public function getBattlenetAccount(): ?string
    {
        return $this->battlenetAccount;
    }

    public function setBattlenetAccount(?string $battlenetAccount): self
    {
        $this->battlenetAccount = $battlenetAccount;

        return $this;
    }

    public function getRegisteredAt(): ?DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(?DateTimeInterface $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getModifiedAt(): ?DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?DateTimeInterface $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getHardware(): ?string
    {
        return $this->hardware;
    }

    public function setHardware(?string $hardware): self
    {
        $this->hardware = $hardware;

        return $this;
    }

    public function getInfoMails(): ?bool
    {
        return $this->infoMails;
    }

    public function setInfoMails(?bool $infoMails): self
    {
        $this->infoMails = $infoMails;

        return $this;
    }

    public function getStatements(): ?string
    {
        return $this->statements;
    }

    public function setStatements(?string $statements): self
    {
        $this->statements = $statements;

        return $this;
    }

    public function getClans(): Collection|array
    {
        return $this->clans;
    }

    public function setClans(Collection|array $clans): self
    {
        $this->clans = $clans;

        return $this;
    }
}
