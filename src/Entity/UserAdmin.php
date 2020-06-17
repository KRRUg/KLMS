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

    /**
     * @ORM\Column(type="array")
     */
    private $permissions = [];

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

    public function hasPermisison(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function addPermisison(string $permission): self
    {
        if (!$this->hasPermisison($permission))
            array_push($this->permissions, $permission);
    }
}
