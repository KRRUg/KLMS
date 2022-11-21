<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginUser implements UserInterface
{
    private readonly User $user;

    /**
     * @var string[]
     */
    private array $roles = [];

    /**
     * LoginUser constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->user->getEmail();
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function addRoles(array $roles): self
    {
        $this->roles = array_merge($this->roles, $roles);

        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        // TODO remove me after upgrade to Symfony 6
        return null;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        // TODO remove me after upgrade to Symfony 6
        return null;
    }

    /**
     * @see UserInterface
     */
    public function getUsername(): string
    {
        // TODO remove me after upgrade to Symfony 6
        return $this->getUserIdentifier();
    }
}
