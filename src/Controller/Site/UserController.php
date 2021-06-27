<?php

namespace App\Controller\Site;

use App\Entity\User;
use App\Form\UserType;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Security\LoginUser;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private IdmManager $manager;
    private IdmRepository $userRepo;
    private LoggerInterface $logger;

    public function __construct(IdmManager $manager, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->logger = $logger;
    }

    public function getUser(): User
    {
        $u = parent::getUser();
        if (!$u instanceof LoginUser) {

        }
        return $u->getUser();
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/user/profile", name="user_profile")
     */
    public function userProfile()
    {
        $user = $this->getUser();

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
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/user/profile/edit/pw", name="user_profile_edit_pw")
     */
    public function userProfileEditPw(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createFormBuilder($user)
            ->add('oldPassword', PasswordType::class, [
                'required' => true,
                'mapped' => false,
                'label' => 'Aktuelles Passwort',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Das Passwort muss übereinstimmen.',
                'required' => true,
                'first_options'  => ['label' => 'Neues Passwort'],
                'second_options' => ['label' => 'Password wiederholen'],
            ])
            ->getForm()
        ;
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get('oldPassword')->getData();
            try{
                if ($this->userRepo->authenticate($user->getEmail(), $data)) {
                    $this->manager->flush();
                    $this->addFlash('success', 'Passwort wurde geändert');
                    return $this->redirectToRoute('user_profile');
                } else {
                    $this->addFlash('error', 'Altes Passwort inkorrekt.');
                }
            } catch (PersistException $e) {
                $this->addFlash('error', 'Passwort konnte nicht geändert werden');
                $this->logger->error('PW change failed');
            }
        }

        return $this->render('site/user/edit.pw.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @Route("/user/profile/edit", name="user_profile_edit")
     */
    public function userProfileEdit(Request $request)
    {
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // TODO: add Support for changing the EMail
            $user = $form->getData();
            try{
                $this->manager->persist($user);
                $this->manager->flush();
                return $this->redirectToRoute('user_profile');
            } catch (PersistException $e) {
                switch($e->getCode()) {
                    case PersistException::REASON_NON_UNIQUE:
                        $this->addFlash('error', "Nickname und/oder Email git es schon.");
                        break;
                    default:
                        $this->addFlash('error', "Unbekannter Fehler beim Speichern.");
                        break;
                }
            }
        }

        return $this->render('site/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
