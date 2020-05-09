<?php

namespace App\Controller\Admin;

use App\Form\Admin\AdminUserEditType;
use App\Security\User;
use App\Service\TextBlockService;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

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
        $users = $this->userService->getAllUsers();
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
    public function edit(string $uuid, Request $request, FlashBagInterface $flashBag)
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

                $flashBag->add('info', 'User erfolgreich bearbeitet!');

                return $this->redirectToRoute('admin_user');
            } else {

                $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');

                return $this->redirectToRoute('admin_user_edit', ['uuid' => $uuid]);
            }

        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
