<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\User;
use App\Form\PermissionType;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\PermissionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/permission", name="permission")
 * @IsGranted("ROLE_ADMIN_SUPER")
 */
class PermissionController extends BaseController
{
    private PermissionService $permissionService;
    private IdmRepository $userRepo;
    private \Symfony\Component\Form\FormFactoryInterface $formFactory;

    public function __construct(PermissionService $permissionService, IdmManager $manager, \Symfony\Component\Form\FormFactoryInterface $formFactory)
    {
        $this->permissionService = $permissionService;
        $this->userRepo = $manager->getRepository(User::class);
        $this->formFactory = $formFactory;
    }

    /**
     * @Route(".{_format}", name="", defaults={"_format"="html"}, methods={"GET"})
     */
    public function index(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $local_admins = $this->permissionService->getAdmins();
        uasort($local_admins, function ($a, $b) {
            return $a[0]->getNickname() < $b[0]->getNickname() ? -1 : 1;
        });

        if ($request->getRequestFormat() === 'json') {
            return $this->apiResponse(
                array_values($local_admins),
                true
            );
        } else {
            $formEdit = $this->formFactory->createNamed('edit', PermissionType::class, null, ['include_user' => true]);
            $formAdd = $this->formFactory->createNamed('new', PermissionType::class, null, ['include_user' => false]);

            return $this->render('admin/permission/index.html.twig', [
                'admins' => $local_admins,
                'form_edit' => $formEdit->createView(),
                'form_add' => $formAdd->createView(),
            ]);
        }
    }

    /**
     * @Route("", name="_add", methods={"POST"})
     */
    public function addPermission(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $form = $this->formFactory->createNamed('new', PermissionType::class);
        $form->submit($data[$form->getName()]);
        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);

            return $this->apiResponse([
                'errors' => $errors,
            ], false, 400);
        }

        $data = $form->getData();
        if (!$this->permissionService->setPermissions($data['user'], $data['perm'])) {
            $form->get('perm')->addError(new FormError('Invalide Berechtigungen gesetzt'));
        }

        return $this->apiResponse([]);
    }

    /**
     * @Route("/{id}", name="_get", methods={"GET"})
     */
    public function getPermission($id): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->userRepo->findOneById($id);
        if (empty($user)) {
            return $this->apiResponse([], false, 404);
        }

        $perm = $this->permissionService->getPermissions($user);

        return $this->apiResponse(['user' => $user, 'perm' => $perm]);
    }

    /**
     * @Route("/{id}", name="_edit", methods={"POST"})
     */
    public function updatePermission(Request $request, $id): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->userRepo->findOneById($id);

        if (empty($user)) {
            return $this->apiResponse([], false, 404);
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $form = $this->formFactory->createNamed('edit', PermissionType::class, null, ['include_user' => true]);
        $form->submit($data[$form->getName()]);
        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);

            return $this->apiResponse([
                'errors' => $errors,
            ], false, 400);
        }

        $data = $form->getData();
        if (!$this->permissionService->setPermissions($user, $data['perm'])) {
            $form->get('perm')->addError(new FormError('Invalide Berechtigungen gesetzt'));
        }

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);

            return $this->apiResponse([
                'errors' => $errors,
            ], false, 400);
        }

        return $this->apiResponse([]);
    }
}
