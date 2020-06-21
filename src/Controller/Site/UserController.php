<?php

namespace App\Controller\Site;

use App\Form\UserEditType;
use App\Form\UserRegisterType;
use App\Security\User;
use App\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UserService $userService, LoggerInterface $appLogger)
    {
        $this->userService = $userService;
        $this->logger = $appLogger;
    }

    /**
     * @Route("/user/profile", name="user_profile")
     */
    public function userProfile()
    {
        if(null === $this->getUser()) {
            // Redirect to Frontpage if not logged in
            return $this->redirect('/');
        }

        $user = $this->userService->getUser($this->getUser()->getUsername());

        return $this->render('site/user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/user/{uuid}", name="user_show", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     */
    public function userShow(string $uuid)
    {

        $user = $this->userService->getUser($uuid);

        return $this->render('site/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/user/profile/edit", name="user_profile_edit")
     */
    public function userProfileEdit(Request $request, FlashBagInterface $flashBag)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->userService->getUser($this->getUser()->getUsername());

        $form = $this->createForm(UserEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // TODO: deny nulling out/changing? Address when already signed up to the Event
            // TODO: deny editing Nickname when Event is in the Next 7(?) Days
            // TODO: add Support for changing the EMail
            // get Data from Form

            /* @var User */
            $userform = $form->getData();

            if(!$this->userService->checkUserAvailability($userform->getNickname()) && $userform->getNickname() !== $user->getNickname()) {
                $form->get('nickname')->addError(new FormError('Nickname wird bereits benutzt!'));

                return $this->render('site/user/profile_edit.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            if($this->userService->editUser($userform)) {

                return $this->redirectToRoute('user_profile');
            } else {

                $flashBag->add('error', 'Es ist ein unerwarteter Fehler aufgetreten');

                return $this->redirectToRoute('user_profile_edit');
            }

        }

        return $this->render('site/user/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, FlashBagInterface $flashBag)
    {
        if(null !== $this->getUser()) {
            // Redirect to Frontpage if already logged in
            return $this->redirect('/');
        }

        $form = $this->createForm(UserRegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // get Data from Form
            $user = $form->getData();

            if(!$this->userService->checkUserAvailability($user['email'])) {
                $form->get('email')->addError(new FormError('EMail wird bereits benutzt!'));

                return $this->render('site/user/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            if(!$this->userService->checkUserAvailability($user['nickname'])) {
                $form->get('nickname')->addError(new FormError('Nickname wird bereits benutzt!'));

                return $this->render('site/user/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            if($this->userService->registerUser($user)) {

                // send the confirmation Email
                //TODO: send the confirmation Email


                $flashBag->add('info', 'Erfolgreich registriert!');

                return $this->redirect('/');
            } else {
                $flashBag->add('error', 'Es ist ein Fehler bei der Registrierung aufgetreten.');

                return $this->redirectToRoute('register');
            }

        }

        return $this->render('site/user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register/check", name="register_check")
     */
    public function checkUsername(Request $request)
    {
        if($this->userService->checkUserAvailability($request->query->get('name'))){
            return new JsonResponse(true);
        } else {
            return new JsonResponse(false);
        }
    }
}
