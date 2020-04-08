<?php


namespace App\Service;


use App\Entity\UserAdmin;
use App\Exception\PermissionException;
use App\Repository\UserAdminsRepository;
use App\Security\UserInfo;
use Doctrine\ORM\EntityManagerInterface;

class AdminManagementService
{
    ////////////////////////////////////////////////
    /// Admin roles names
    ///////////////////////////////////////////////
    const ADMIN_SUPER = "ADMIN_SUPER";
    const ADMIN_CONTENT = "ADMIN_CONTENT";
    const ADMIN_ADMISSION = "ADMIN_ADMISSION";
    const ADMIN_NEWS = "ADMIN_NEWS";

    const PERMISSIONS = [
        self::ADMIN_SUPER,
        self::ADMIN_CONTENT,
        self::ADMIN_ADMISSION,
        self::ADMIN_NEWS,
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

    public function hasPermission(string $permission, UserInfo $user = null): bool
    {
        if (!$this->validPermission($permission)) {
            return false;
        }
        if (empty($user)) {
            $user = $this->userService->getCurrentUser();
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

    public function isUserAdmin(UserInfo $user = null): bool
    {
        if (empty($user)) {
            $user = $this->userService->getCurrentUser();
        }
        if (empty($user)) {
            return false;
        }
        $admin = $this->repo->findByUser($user);
        return !empty($admin) && count($admin->getPermissions()) > 0;
    }

    /**
     * Checks if a user has a certain permission
     * @param string $permission
     * @param UserInfo $user The User to check. When empty, check current user.
     * @throws PermissionException If $user has not $permission
     */
    public function checkAndThrow(string $permission, UserInfo $user = null)
    {
        if (empty($user)) {
            $user = $this->userService->getCurrentUser();
        }
        if (empty($user) || !$this->hasPermission($user, $permission)) {
            throw new PermissionException($permission);
        }
    }

    public function grantPermission(string $permisison, UserInfo $user)
    {
        $this->checkAndThrow(self::ADMIN_SUPER);

        if (!$this->validPermission($permisison))
            return;

        $admin = $this->repo->findByUser($user);
        if (empty($admin)) {
            $admin = new UserAdmin($user->getUuid());
        }
        $admin->addPermisison($permisison);
        $this->em->persist($admin);
        $this->em->flush();
    }
}