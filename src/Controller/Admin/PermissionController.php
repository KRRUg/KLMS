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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/permission', name: 'permission')]
#[IsGranted('ROLE_ADMIN_SUPER')]
class PermissionController extends BaseController
{
    private readonly PermissionService $permissionService;
    private readonly IdmRepository $userRepo;
    private readonly FormFactoryInterface $formFactory;

    public function __construct(PermissionService $permissionService, IdmManager $manager, FormFactoryInterface $formFactory)
    {
        $this->permissionService = $permissionService;
        $this->userRepo = $manager->getRepository(User::class);
        $this->formFactory = $formFactory;
    }

    #[Route(path: '.{_format}', name: '', defaults: ['_format' => 'html'], methods: ['GET'])]
    public function index(Request $request): Response
    {
        $local_admins = $this->permissionService->getAdmins();
        uasort($local_admins, fn ($a, $b) => $a[0]->getNickname() < $b[0]->getNickname() ? -1 : 1);

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

    #[Route(path: '', name: '_add', methods: ['POST'])]
    public function addPermission(Request $request): Response
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
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

    #[Route(path: '/{id}', name: '_get', methods: ['GET'])]
    public function getPermission($id): Response
    {
        $user = $this->userRepo->findOneById($id);
        if (empty($user)) {
            return $this->apiResponse([], false, 404);
        }

        $perm = $this->permissionService->getPermissions($user);

        return $this->apiResponse(['user' => $user, 'perm' => $perm]);
    }

    #[Route(path: '/{id}', name: '_edit', methods: ['POST'])]
    public function updatePermission(Request $request, $id): Response
    {
        $user = $this->userRepo->findOneById($id);

        if (empty($user)) {
            return $this->apiResponse([], false, 404);
        }

        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
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
