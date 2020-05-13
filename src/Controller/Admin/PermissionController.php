<?php

namespace App\Controller\Admin;

use App\Form\PermissionType;
use App\Service\PermissionService;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_ADMIN_SUPER")
 */
class PermissionController extends AbstractController
{
    private $permissionService;
    private $userService;

    public function __construct(PermissionService $permissionService, UserService $userService)
    {
        $this->permissionService = $permissionService;
        $this->userService = $userService;
    }

    /**
     * @Route("/permission", name="permission", methods={"GET"})
     */
    public function index()
    {
        $local_admins = $this->permissionService->getAdmins();
        $form = $this->createForm(PermissionType::class);
        return $this->render('admin/permission/index.html.twig', [
            'admins' => $local_admins,
            'form' => $form->createView(),
            'show' => false
        ]);
    }

    /**
     * @Route("/permission", name="permission_edit", methods={"POST"})
     */
    public function post(Request $request)
    {
        $form = $this->createForm(PermissionType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // TODO remove this temporary solution
            $data = $form->getData();
            $user = $this->userService->getUsersByNickname($data['user']);
            if (empty($user)){
                $form->get('user')->addError(new FormError("Benutzer nicht gefunden."));
            } else {
                $user = $user[0];
                if (!$this->permissionService->setPermissions($user, $data['perm'])) {
                    $form->get('perm')->addError(new FormError("Invalide Berechtigungen gesetzt"));
                }
            }
        }
        if (!$form->isValid()){
            $local_admins = $this->permissionService->getAdmins();
            return $this->render('admin/permission/index.html.twig', [
                'admins' => $local_admins,
                'form' => $form->createView(),
                'show' => true
            ]);
        }

        return $this->redirectToRoute("admin_permission");
    }
}
