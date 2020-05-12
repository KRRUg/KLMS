<?php

namespace App\Controller\Admin;

use App\Service\PermissionService;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN_SUPER")
 */
class PermissionController extends AbstractController
{
    private $permissionService;

    public function __construct(PermissionService $permissionService, UserService $userService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * @Route("/permission", name="permission")
     */
    public function index()
    {
        $local_admins = $this->permissionService->getAdmins();

        return $this->render('admin/permission/index.html.twig', [
            'admins' => $local_admins,
        ]);
    }
}
