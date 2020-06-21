<?php

namespace App\Security;

use App\Service\UserService;
use App\Service\PermissionService;
use App\Service\GamerService;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;


class UserProvider implements UserProviderInterface
{
    private $userService;
    private $permissionService;
    private $gamerService;

    public function __construct(
        UserService $userService,
        PermissionService $permissionService,
        GamerService $gamerService
    ) {
        $this->userService = $userService;
        $this->permissionService = $permissionService;
        $this->gamerService = $gamerService;
    }

    private function loadAdminRoles(LoginUser $user)
    {
        $perm = $this->permissionService->handleLogin($user->getUser());
        if (!empty($perm)) {
            $roles = array_map(function (string $p) { return "ROLE_" . $p; }, $perm);
            array_push($roles, "ROLE_ADMIN");
            $user->addRoles($roles);
        }
    }

    private function loadUserRoles(LoginUser $user)
    {
        $roles = [];
//        $gamer = $this->gr->find($userGuid);
//        if ($gamer) {
//            if ($gamer->getPayed()) {
//                array_push($roles, "ROLE_USER_PAYED");
//            }
//            // check if user has seat,...
//        }
        $user->addRoles($roles);
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @param $username
     * @return UserInterface
     *
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username)
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        $user = $this->userService->getUser($username);
        if (empty($user)) {
            throw new UsernameNotFoundException();
        }

        $lu = new LoginUser($user);
        $this->loadUserRoles($lu);
        $this->loadAdminRoles($lu);
        return $lu;
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
        if (!$user instanceof LoginUser) {
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
        return LoginUser::class === $class;
    }
}
