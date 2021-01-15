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
 * @Idm\Entity(path="/clans", authorize=true)
 */
class Clan
{
    /**
     * @Assert\Uuid(strict=false)
     * @Groups({"read"})
     */
    private ?UuidInterface $uuid = null;

    /**
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Assert\Length(
     *      min = 1,
     *      max = 64,
     *      minMessage = "The name must be at least {{ limit }} characters long",
     *      maxMessage = "The name cannot be longer than {{ limit }} characters",
     *      allowEmptyString="false",
     *      groups = {"Default", "Transfer", "Create"}
     * )
     * @Groups({"read", "write"})
     */
    private ?string $name = null;

    /**
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
    private ?string $joinPassword = null;

    /**
     * @Groups({"read"})
     */
    private ?DateTimeInterface $createdAt = null;

    /**
     * @Groups({"read"})
     */
    private ?DateTimeInterface $modifiedAt = null;

    /**
     * @Groups({"read"})
     * @Idm\Collection(class="App\Entity\User")
     */
    private $users;

    /**
     * @Groups({"read"})
     * @Idm\Collection(class="App\Entity\User")
     */
    private $admins;

    /**
     * @Assert\Length(
     *      min = 1,
     *      max = 10,
     *      minMessage = "The clantag must be at least {{ limit }} characters long",
     *      maxMessage = "The clantag cannot be longer than {{ limit }} characters",
     *      allowEmptyString="false",
     *      groups = {"Default", "Transfer", "Create"}
     * )
     * @Assert\NotBlank(groups={"Default", "Create"})
     * @Groups({"read", "write"})
     */
    private ?string $clantag = null;

    /**
     * @Assert\Url(groups={"Default", "Transfer"})
     * @Groups({"read", "write"})
     */
    private ?string $website = null;

    /**
     * @Assert\Length(
     *      max = 4096,
     *      maxMessage = "The clan description cannot be longer than {{ limit }} characters",
     *      groups = {"Default", "Transfer"}
     * )
     * @Groups({"read", "write"})
     */
    private ?string $description = null;

    /**
     * @return UuidInterface
     */
    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface $uuid
     * @return Clan
     */
    public function setUuid(UuidInterface $uuid): Clan
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Clan
     */
    public function setName(string $name): Clan
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getJoinPassword(): ?string
    {
        return $this->joinPassword;
    }

    /**
     * @param string $joinPassword
     * @return Clan
     */
    public function setJoinPassword(string $joinPassword): Clan
    {
        $this->joinPassword = $joinPassword;
        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): Clan
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getModifiedAt(): ?DateTimeInterface
    {
        return $this->modifiedAt;
    }


    public function setModifiedAt(DateTimeInterface $modifiedAt): Clan
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function setUsers($users): Clan
    {
        $this->users = $users;
        return $this;
    }

    public function getAdmins()
    {
        return $this->admins;
    }

    public function setAdmins($admins): Clan
    {
        $this->admins = $admins;
        return $this;
    }

    /**
     * @return string
     */
    public function getClantag(): ?string
    {
        return $this->clantag;
    }

    /**
     * @param string $clantag
     * @return Clan
     */
    public function setClantag(string $clantag): Clan
    {
        $this->clantag = $clantag;
        return $this;
    }

    /**
     * @return string
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param string $website
     * @return Clan
     */
    public function setWebsite(string $website): Clan
    {
        $this->website = $website;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Clan
     */
    public function setDescription(string $description): Clan
    {
        $this->description = $description;
        return $this;
    }
}
