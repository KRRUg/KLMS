<?php

namespace App\Controller\Admin;

use App\Form\Admin\AdminUserEditType;
use App\Security\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN_USER")
 */
class UserController extends AbstractController
{
    /**
     * @var UserService
     */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/user", name="user", methods={"GET"})
     */
    public function index()
    {
        //TODO: implement Client Pagination
        $users = $this->userService->queryUsers(null, null, 999999);
        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);

    }

    /**
     * @Route("/user/{uuid}", name="user_show", methods={"GET"})
     */
    public function show(string $uuid)
    {
        $user = $this->userService->getUser($uuid);

        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/user/{uuid}/edit", name="user_edit", methods={"GET", "POST"})
     */
    public function edit(string $uuid, Request $request)
    {
        $user = $this->userService->getUser($uuid);

        $form = $this->createForm(AdminUserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // get Data from Form

            /* @var User */
            $userform = $form->getData();

            if(!$this->userService->checkUserAvailability($userform->getNickname()) && $userform->getNickname() !== $user->getNickname()) {
                $form->get('nickname')->addError(new FormError('Nickname wird bereits benutzt!'));

                return $this->render('admin/user/edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            if($this->userService->editUser($userform)) {

                $this->addFlash('info', 'User erfolgreich bearbeitet!');

                return $this->redirectToRoute('admin_user');
            } else {

                $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');

                return $this->redirectToRoute('admin_user_edit', ['uuid' => $uuid]);
            }

        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
