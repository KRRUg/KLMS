<?php


namespace App\Model;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


class ClanModel
{


    /**
     * The internal primary identity key.
     *
     * @Groups({"default", "clanview"})
     * @Assert\NotBlank
     */
    protected $uuid;

    /**
     * @Groups({"default", "clanview"})
     * @Assert\NotBlank
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $name;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $joinPassword;

    /**
     * @Groups("clanview")
     */
    private $createdAt;

    /**
     * @Groups("clanview")
     */
    private $modifiedAt;

    /**
     * @Groups("clanview")
     */
    private $users;

    /**
     * @Groups({"default", "clanview"})
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\Length(max="24")
     */
    private $clantag;

    /**
     * @Groups({"default", "clanview"})
     * @Assert\Type("string")
     * @Assert\Url()
     * @Assert\Length(max="255")
     */
    private $website;

    /**
     * @Groups({"default", "clanview"})
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $description;


    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getJoinPassword(): ?string
    {
        return $this->joinPassword;
    }

    public function setJoinPassword(string $joinPassword): self
    {
        $this->joinPassword = $joinPassword;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    /**
     * @return null|array|UserClanModel[]
     */
    public function getUsers(): ?array
    {
        return $this->users;
    }

    public function setUsers(array $users)
    {
        $this->users = $users;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
    }


    public function getClantag(): ?string
    {
        return $this->clantag;
    }

    public function setClantag(string $clantag): self
    {
        $this->clantag = $clantag;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

}
