<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Form\PermissionType;
use App\Service\PermissionService;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/permission", name="permission_")
 * @IsGranted("ROLE_ADMIN_SUPER")
 */
class PermissionController extends BaseController
{
    private $permissionService;
    private $userService;

    public function __construct(PermissionService $permissionService, UserService $userService)
    {
        $this->permissionService = $permissionService;
        $this->userService = $userService;
    }

    /**
     * @Route("/", name="index", methods={"GET"})
     */
    public function index(Request $request)
    {
        $local_admins = $this->permissionService->getAdmins();
        uasort($local_admins, function ($a, $b) {
            return $a[0]->getNickname() < $b[0]->getNickname() ? -1 : 1;
        });

        if ($this->acceptsJson($request)){
            return $this->createApiResponse(
                array_values($local_admins)
            );
        } else {
            $form = $this->createForm(PermissionType::class);
            return $this->render('admin/permission/index.html.twig', [
                'admins' => $local_admins,
                'form' => $form->createView(),
                'show' => false
            ]);
        }
    }

    /**
     * @Route("/", name="create", methods={"POST"})
     */
    public function update(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $form = $this->createForm(PermissionType::class);
        $form->submit($data);
        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);

            return $this->createApiResponse([
                'errors' => $errors
            ], 400);
        }

        // TODO current user is not allowed to remove super permission
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

        if (!$form->isValid()){
            $errors = $this->getErrorsFromForm($form);

            return $this->createApiResponse([
                'errors' => $errors
            ], 400);
        }

        return $this->createApiResponse([]);
    }
}
