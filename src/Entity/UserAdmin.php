<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserAdminsRepository")
 */
class UserAdmin
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    private ?UuidInterface $uuid;

    /**
     * @ORM\Column(type="array")
     */
    private $permissions = [];

    public function __construct(?UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid)
    {
        $this->uuid = $uuid;
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
        if (!$this->hasPermisison($permission)) {
            array_push($this->permissions, $permission);
        }
    }
}
