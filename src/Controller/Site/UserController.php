<?php

namespace App\Controller\Site;

use App\Entity\User;
use App\Form\UserType;
use App\Helper\EmailRecipient;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Security\LoginUser;
use App\Service\EmailService;
use App\Service\GamerService;
use App\Service\SettingService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private readonly IdmManager $manager;
    private readonly IdmRepository $userRepo;
    private readonly EmailService $emailService;
    private readonly SettingService $settingService;
    private readonly GamerService $gamerService;
    private readonly LoggerInterface $logger;

    public function __construct(IdmManager $manager,
                                EmailService $emailService,
                                SettingService $settingService,
                                GamerService $gamerService,
                                LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->emailService = $emailService;
        $this->settingService = $settingService;
        $this->gamerService = $gamerService;
        $this->logger = $logger;
    }

    public function getUser(): User
    {
        $u = parent::getUser();
        if (!$u instanceof LoginUser) {
            $this->logger->critical('User Object of invalid type in session found.');
        }

        return $u->getUser();
    }

    private const SHOW_LIMIT = 20;

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/user", name="user")
     */
    public function index(Request $request): Response
    {
        if (!$this->settingService->get('community.enabled', false)) {
            throw $this->createNotFoundException();
        }

        $search = $request->query->get('q', '');
        $page = $request->query->getInt('page', 1);
        $page = $page < 1 ? 1 : $page;

        if ($this->settingService->get('community.all', false)) {
            $collection = $this->userRepo->findFuzzy($search);
            $users = $collection->getPage($page, self::SHOW_LIMIT);
            $count = $collection->count();
        } else {
            $gamers = array_values($this->gamerService->getGamers(false));
            $users = array_map(fn(array $in) => $in['user'], $gamers);
            if (!empty($search)) {
                $users = array_filter($users, fn(User $u) => stripos($u->getNickname(), (string) $search) !== false || stripos($u->getFirstname(), (string) $search) !== false);
            }
            usort($users, fn(User $a, User $b) => $a->getNickname() <=> $b->getNickname());
            $count = count($users);
            $users = array_slice($users, ($page - 1) * self::SHOW_LIMIT, self::SHOW_LIMIT);
        }

        return $this->render('site/user/list.html.twig', [
            'search' => $search,
            'users' => $users,
            'page' => $page,
            'total' => $count,
            'limit' => self::SHOW_LIMIT,
        ]);
    }

    /**
     * @IsGranted("IS_AUTHENTICATED_REMEMBERED")
     * @Route("/user/profile", name="user_profile")
     */
    public function userProfile(): Response
    {
        $user = $this->getUser();

        return $this->render('site/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/user/{uuid}", name="user_show", requirements= {"uuid"="[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}"})
     */
    public function userShow(string $uuid): Response
    {
        $user = $this->userRepo->findOneById($uuid);

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')
            && $user === $this->getUser()) {
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
    public function userProfileEditPw(Request $request): Response
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
                'first_options' => ['label' => 'Neues Passwort'],
                'second_options' => ['label' => 'Passwort wiederholen'],
            ])
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get('oldPassword')->getData();
            try {
                if ($this->userRepo->authenticate($user->getEmail(), $data)) {
                    $this->manager->flush();
                    $this->addFlash('success', 'Passwort wurde geändert');
                    $this->emailService->scheduleHook(
                        EmailService::APP_HOOK_CHANGE_NOTIFICATION,
                        EmailRecipient::fromUser($user), [
                            'message' => 'Dein Passwort wurde geändert',
                        ]
                    );

                    return $this->redirectToRoute('user_profile');
                } else {
                    $this->addFlash('error', 'Altes Passwort inkorrekt.');
                }
            } catch (PersistException) {
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
    public function userProfileEdit(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // TODO: add Support for changing the EMail
            $user = $form->getData();
            try {
                $this->manager->persist($user);
                $this->manager->flush();

                return $this->redirectToRoute('user_profile');
            } catch (PersistException $e) {
                match ($e->getCode()) {
                    PersistException::REASON_NON_UNIQUE => $this->addFlash('error', 'Nickname und/oder Email gibt es schon.'),
                    default => $this->addFlash('error', 'Unbekannter Fehler beim Speichern.'),
                };
            }
        }

        return $this->render('site/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
