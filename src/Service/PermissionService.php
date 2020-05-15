<?php


namespace App\Service;


use App\Entity\UserAdmin;
use App\Exception\PermissionException;
use App\Repository\UserAdminsRepository;
use App\Security\UserInfo;
use Doctrine\ORM\EntityManagerInterface;

final class PermissionService
{
    ////////////////////////////////////////////////
    /// Admin roles names
    ///////////////////////////////////////////////
    const ADMIN_SUPER = "ADMIN_SUPER";         // May grant admin rights to other uses
    const ADMIN_CONTENT = "ADMIN_CONTENT";     // May edit page content and navigation
    const ADMIN_ADMISSION = "ADMIN_ADMISSION"; // May edit users registration state
    const ADMIN_NEWS = "ADMIN_NEWS";           // May edit and publish news
    const ADMIN_MAIL = "ADMIN_MAIL";           // May edit and send newsletters and emails
    // extend here

    const PERMISSIONS = [
        self::ADMIN_SUPER,
        self::ADMIN_CONTENT,
        self::ADMIN_ADMISSION,
        self::ADMIN_NEWS,
        self::ADMIN_MAIL,
        // extend here
    ];

    private $repo;
    private $em;
    private $userService;

    public function __construct(UserService $userService, EntityManagerInterface $em, UserAdminsRepository $repo)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->userService = $userService;
    }

    public function validPermission(string $permission): bool
    {
        $permission = strtoupper($permission);
        return in_array($permission, self::PERMISSIONS);
    }

    public function hasPermission(string $permission, UserInfo $user): bool
    {
        if (!$this->validPermission($permission)) {
            return false;
        }
        if (empty($user)) {
            return false;
        }
        $admin = $this->repo->findByUser($user);
        if (empty($admin)) {
            return false;
        }
        return in_array($permission, $admin->getPermissions());
    }

    public function hasSessionPermission(string $permission): bool
    {
        if (!$this->validPermission($permission)) {
            return false;
        }
        $role = "ROLE_" . $permission;
        $user = $this->userService->getCurrentUser();
        if (empty($user)) {
            return false;
        }
        return $user->hasRole($role);
    }

    /**
     * Checks if a user has a certain permission
     * @param string $permission
     * @throws PermissionException If $user has not $permission
     */
    public function checkAndThrow(string $permission)
    {
        if (!$this->hasSessionPermission($permission)) {
            throw new PermissionException($permission);
        }
    }

    public function isSessionAdmin(): bool
    {
        $user = $this->userService->getCurrentUser();
        if (empty($user)) {
            return false;
        }
        return $user->hasRole("ROLE_ADMIN");
    }

    public function grantPermission(string $permission, UserInfo $user) : bool
    {
        $this->checkAndThrow(self::ADMIN_SUPER);

        if (!$this->validPermission($permission))
            return false;

        $admin = $this->repo->findByUser($user);
        if (empty($admin)) {
            $admin = new UserAdmin($user->getUuid());
        }
        $admin->addPermisison($permission);
        $this->em->persist($admin);
        $this->em->flush();
        return true;
    }

    public function getPermissions(UserInfo $user) : array
    {
        $ret = $this->repo->findByUser($user)->getPermissions();
        if (empty($ret))
            return [];
        return $ret;
    }

    public function setPermissions(UserInfo $user, array $permissions) : bool
    {
        $this->checkAndThrow(self::ADMIN_SUPER);

        if (is_null($permissions))
            return false;

        if (count(array_diff($permissions, self::PERMISSIONS)) > 0)
            return false;

        $admin = $this->repo->findByUser($user);

        if (empty($admin) && empty($permissions))
            return true;

        if (empty($permissions)) {
            $this->em->remove($admin);
            $this->em->flush();
            return true;
        }

        if(empty($admin))
            $admin = new UserAdmin($user->getUuid());

        $admin->setPermissions($permissions);
        $this->em->persist($admin);
        $this->em->flush();
        return true;
    }

    public function getAdmins() : array
    {
        $admins = $this->repo->findAll();
        $admins = array_filter($admins, function (UserAdmin $a) { return !empty($a->getPermissions()); });
        $ids = array_map(function (UserAdmin $a) {return $a->getId(); }, $admins);
        $users = $this->userService->getUsersByUuid($ids, true);

        $ret = [];
        foreach ($admins as $admin) {
            $u = $users[$admin->getId()];
            $p = $admin->getPermissions();
            $ret[$u->getUuid()] = array($u, $p);
        }
        return $ret;
    }
}