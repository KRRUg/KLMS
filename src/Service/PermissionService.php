<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserAdmin;
use App\Exception\PermissionException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserAdminsRepository;
use App\Security\LoginUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

final class PermissionService
{
    // //////////////////////////////////////////////
    // / Admin roles names
    // /////////////////////////////////////////////
    public const ADMIN_SUPER = 'ADMIN_SUPER';         // May grant admin rights to other uses
    public const ADMIN_CONTENT = 'ADMIN_CONTENT';     // May edit page content and navigation
    public const ADMIN_ADMISSION = 'ADMIN_ADMISSION'; // May edit users registration state
    public const ADMIN_NEWS = 'ADMIN_NEWS';           // May edit and publish news
    public const ADMIN_MAIL = 'ADMIN_MAIL';           // May edit and send newsletters and emails
    public const ADMIN_USER = 'ADMIN_USER';           // May edit users and clans
    public const ADMIN_PAYMENT = 'ADMIN_PAYMENT';     // May set Gamers to paid/unpaid
    public const ADMIN_CHECKIN = 'ADMIN_CHECKIN';     // May check in Gamers at the LAN
    public const ADMIN_SEATMAP = 'ADMIN_SEATMAP';     // May edit seatmap and assign gamers seats
    public const ADMIN_TOURNEY = 'ADMIN_TOURNEY';     // May edit tourneys
    // extend here

    public const PERMISSIONS = [
        self::ADMIN_SUPER,
        self::ADMIN_CONTENT,
        self::ADMIN_ADMISSION,
        self::ADMIN_NEWS,
        self::ADMIN_MAIL,
        self::ADMIN_USER,
        self::ADMIN_PAYMENT,
        self::ADMIN_CHECKIN,
        self::ADMIN_SEATMAP,
        self::ADMIN_TOURNEY
        // extend here
    ];

    private readonly UserAdminsRepository $repo;
    private readonly EntityManagerInterface $em;
    private readonly IdmRepository $userRepo;
    private readonly Security $security;

    public function __construct(
        Security $security,
        EntityManagerInterface $em,
        UserAdminsRepository $repo,
        IdmManager $manager
    ) {
        $this->repo = $repo;
        $this->em = $em;
        $this->security = $security;
        $this->userRepo = $manager->getRepository(User::class);
    }

    public function validPermission(string $permission): bool
    {
        $permission = strtoupper($permission);

        return in_array($permission, self::PERMISSIONS);
    }

    public function hasPermission(string $permission, User $user): bool
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

    private function getCurrentLoginUser(): ?LoginUser
    {
        $user = $this->security->getUser();
        if (empty($user) || !($user instanceof LoginUser)) {
            return null;
        }

        return $user;
    }

    public function hasSessionPermission(string $permission): bool
    {
        if (!$this->validPermission($permission)) {
            return false;
        }
        $role = 'ROLE_'.$permission;
        $user = $this->getCurrentLoginUser();
        if (empty($user)) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Checks if a user has a certain permission.
     *
     * @throws PermissionException If $user has not $permission
     */
    public function checkAndThrow(string $permission): void
    {
        if (!$this->hasSessionPermission($permission)) {
            throw new PermissionException($permission);
        }
    }

    public function grantPermission(string $permission, User $user): bool
    {
        $this->checkAndThrow(self::ADMIN_SUPER);

        if (!$this->validPermission($permission)) {
            return false;
        }

        $admin = $this->repo->findByUser($user);
        if (empty($admin)) {
            $admin = new UserAdmin($user->getUuid());
        }
        $admin->addPermission($permission);
        $this->em->persist($admin);
        $this->em->flush();

        return true;
    }

    public function getPermissions(User $user): array
    {
        $adminUser = $this->repo->findByUser($user);
        if (empty($adminUser)) {
            return [];
        }

        return $adminUser->getPermissions();
    }

    public function setPermissions(User $user, array $permissions): bool
    {
        $this->checkAndThrow(self::ADMIN_SUPER);

        if (is_null($permissions)) {
            return false;
        }

        if (count(array_diff($permissions, self::PERMISSIONS)) > 0) {
            return false;
        }

        $admin = $this->repo->findByUser($user);

        if (empty($admin) && empty($permissions)) {
            return true;
        }

        if (empty($permissions)) {
            $this->em->remove($admin);
            $this->em->flush();

            return true;
        }

        if (empty($admin)) {
            $admin = new UserAdmin($user->getUuid());
        }

        $admin->setPermissions($permissions);
        $this->em->persist($admin);
        $this->em->flush();

        return true;
    }

    public function getAdmins(): array
    {
        $admins = $this->repo->findAll();
        $admins = array_filter($admins, fn (UserAdmin $a) => !empty($a->getPermissions()));
        $ids = array_map(fn (UserAdmin $a) => $a->getUuid()->toString(), $admins);
        $admins = array_combine($ids, $admins);
        $users = $this->userRepo->findById($ids);

        $ret = [];
        foreach ($users as $user) {
            $uuid = $user->getUuid()->toString();
            $permissions = $admins[$uuid]->getPermissions();
            $ret[$uuid] = [$user, $permissions];
        }

        return $ret;
    }

    /**
     * @param User $user The user to be logged in
     *
     * @return array The permissions of the User
     */
    public function handleLogin(User $user): array
    {
        $userAdmin = $this->repo->findByUser($user);
        if ($user->getIsSuperadmin()) {
            if (empty($userAdmin)) {
                $userAdmin = new UserAdmin($user->getUuid());
            }
            if (!empty(array_diff(PermissionService::PERMISSIONS, $userAdmin->getPermissions()))) {
                $userAdmin->setPermissions(PermissionService::PERMISSIONS);
                $this->em->persist($userAdmin);
                $this->em->flush();
            }
        }
        if (!empty($userAdmin)) {
            return $userAdmin->getPermissions();
        }

        return [];
    }
}
