<?php

namespace App\Controller\Site;

use App\Entity\User;
use App\Form\UserRegisterType;
use App\Form\UserType;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Security\LoginFormAuthenticator;
use App\Security\LoginUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class UserController extends AbstractController
{
    private IdmManager $manager;
    private IdmRepository $userRepo;

    public function __construct(IdmManager $manager)
    {
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/user/profile", name="user_profile")
     */
    public function userProfile()
    {
        $user = $this->getUser()->getUser();

        return $this->render('site/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/user/{uuid}", name="user_show", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     */
    public function userShow(string $uuid)
    {
        $user = $this->userRepo->findOneById($uuid);

        if ($this->isGranted("IS_AUTHENTICATED_REMEMBERED")
            && $user === $this->getUser()->getUser()) {
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('site/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/user/profile/edit", name="user_profile_edit")
     */
    public function userProfileEdit(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser()->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // TODO: deny nulling out/changing? Address when already signed up to the Event
            // TODO: deny editing Nickname when Event is in the Next 7(?) Days
            // TODO: add Support for changing the EMail

            $user = $form->getData();
            $this->manager->persist($user);
            $this->manager->flush();
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('site/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, LoginFormAuthenticator $login, GuardAuthenticatorHandler $guard, UserProviderInterface $userProvider)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')){
            return $this->redirectToRoute('user_profile');
        }

        $form = $this->createForm(UserRegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            try {
                $this->manager->persist($user);
                $this->manager->flush();
                $this->addFlash('info', 'Erfolgreich registriert!');
                return $guard->authenticateUserAndHandleSuccess(new LoginUser($user), $request, $login, 'main');
            } catch (PersistException $e) {
                switch ($e->getCode()) {
                    case PersistException::REASON_NON_UNIQUE:
                        $this->addFlash('error', 'Nickname und/oder Email Adresse schon in vergeben');
                        break;
                    default:
                        $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
                        break;
                }
            }
        }

        return $this->render('site/user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
