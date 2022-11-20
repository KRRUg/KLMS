<?php

namespace App\Entity;

use App\Repository\UserAdminsRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: UserAdminsRepository::class)]
class UserAdmin
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?UuidInterface $uuid;

    #[ORM\Column(type: 'array')]
    private array $permissions = [];

    public function __construct(?UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function hasPermission(string $permission): bool
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

    public function addPermission(string $permission): self
    {
        if (!$this->hasPermission($permission)) {
            $this->permissions[] = $permission;
        }

        return $this;
    }
}
