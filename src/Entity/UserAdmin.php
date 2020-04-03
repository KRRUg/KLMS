<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserAdminsRepository")
 */
class UserAdmin
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="guid", unique=true)
     */
    private $guid;

    public function __construct(string $guid)
    {
        $this->guid = $guid;
    }

    public function getId(): ?string
    {
        return $this->guid;
    }

    public function setId(string $guid)
    {
        $this->guid = $guid;
    }
}
