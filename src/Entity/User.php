<?php


namespace App\Entity;

use App\Idm\Annotation as Idm;
use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 *
 * @Idm\Entity(path="/users")
 */
class User
{
    // TODO add length asserts for all strings

    /**
     * @Assert\Uuid(strict=false)
     * @Groups({"read"})
     */
    private ?UuidInterface $uuid = null;

    /**
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Assert\Email(groups={"Default", "Transfer", "Create"})
     * @Groups({"read", "write"})
     */
    private ?string $email = null;

    /**
     * @var string The hashed password
     * @Assert\Length(
     *      min = 6,
     *      max = 128,
     *      minMessage = "The password must be at least {{ limit }} characters long",
     *      maxMessage = "The password cannot be longer than {{ limit }} characters",
     *      allowEmptyString="false",
     *      groups = {"Transfer", "Create"}
     * )
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Groups({"write"})
     */
    private ?string $password = null;

    /**
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Assert\Length(
     *      min = 1,
     *      max = 64,
     *      minMessage = "The nickname must be at least {{ limit }} characters long",
     *      maxMessage = "The nickname cannot be longer than {{ limit }} characters",
     *      allowEmptyString="false",
     *      groups = {"Default", "Transfer", "Create"}
     * )
     * @Groups({"read", "write"})
     */
    private ?string $nickname = null;

    /**
     * @Groups({"read"})
     * TODO check if status can be IDM internal
     */
    private ?int $status = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?string $firstname = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?string $surname = null;

    /**
     * @Assert\Length(
     *      min = 1,
     *      max = 8,
     *      minMessage = "The postcode must be at least {{ limit }} characters long",
     *      maxMessage = "The postcode cannot be longer than {{ limit }} characters",
     *      allowEmptyString="false",
     *      groups = {"Default", "Transfer", "Create"}
     * )
     * @Groups({"read", "write"})
     */
    private ?string $postcode = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?string $city = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?string $street = null;

    /**
     * @Assert\Country(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private ?string $country = null;

    /**
     * @Groups({"read", "write"})
     * @Assert\Regex("/^[+]?\d([ \/()]?\d)*$/", message="Invalid phone number format.", groups={"Default", "Transfer"})
     */
    private ?string $phone = null;

    /**
     * @Assert\Choice({"m","w","d"}, groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private ?string $gender = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?bool $emailConfirmed = null;

    /**
     * @Groups({"read"})
     */
    private ?bool $isSuperadmin = null;

    /**
     * @Assert\Url(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private ?string $website = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?string $steamAccount = null;

    /**
     * @Groups({"read"})
     */
    private ?DateTimeInterface $registeredAt = null;

    /**
     * @Groups({"read"})
     */
    private ?DateTimeInterface $modifiedAt = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?string $hardware = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?bool $infoMails = null;

    /**
     * @Groups({"read", "write"})
     */
    private ?string $statements = null;

    /**
     * @Groups({"read"})
     * @Idm\Collection(class="App\Entity\Clan")
     */
    private $clans;

    /**
     * @Assert\Date(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private ?DateTimeInterface $birthdate = null;


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

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
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

    public function setEmailConfirmed(bool $emailConfirmed): self
    {
        $this->emailConfirmed = $emailConfirmed;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;

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

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
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

    public function getRegisteredAt(): ?\DateTimeInterface
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeInterface $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTimeInterface $modifiedAt): self
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

    public function setInfoMails(bool $infoMails): self
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

    public function getClans()
    {
        return $this->clans;
    }

    public function setClans($clans): self
    {
        $this->clans = $clans;
        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;

        return $this;
    }
}