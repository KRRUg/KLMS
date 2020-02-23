<?php

namespace App\Security;

use App\Repository\UserAdminsRepository;
use App\Repository\UserGamerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private $ar;
    private $gr;

    public function __construct(UserAdminsRepository $ar, UserGamerRepository $gr)
    {
        $this->ar = $ar;
        $this->gr = $gr;
    }

    private function loadUserRoles($userGuid)
    {
        $ret = [];
        if ($this->ar->find($userGuid)) {
            array_push($ret, "ROLE_ADMIN");
        }
        $gamer = $this->gr->find($userGuid);
        if ($gamer) {
            if ($gamer->getPayed()) {
                array_push($ret, "ROLE_PAYED_USER");
            }
            // TODO check if user has seat,...
        }
        return $ret;
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        if ($username === "admin") {
            $user = new User();
            $user->setUsername($username);
            $user->setClans([]);
            $user->setUuid("c11ed9b0-e060–4aec-b513-e17c24df2c70");
            $user->setRoles($this->loadUserRoles($user->getUuid()));
            return $user;
        } else if ($username === "hata") {
            $user = new User();
            $user->setUsername($username);
            $user->setClans([]);
            $user->setUuid("a3ba1298-4aa0–4aa1-5bb2-e18c98fa0980");
            $user->setRoles($this->loadUserRoles($user->getUuid()));
            return $user;
        }
        throw new UsernameNotFoundException();
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        // TODO implement
        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }

    /**
     * Upgrades the encoded password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        // TODO: when encoded passwords are in use, this method should:
        // 1. persist the new password in the user storage
        // 2. update the $user object with $user->setPassword($newEncodedPassword);
    }
}
