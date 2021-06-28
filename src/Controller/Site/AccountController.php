<?php

namespace App\Controller\Site;

use App\Entity\User;
use App\Exception\TokenException;
use App\Form\UserRegisterType;
use App\Helper\EmailRecipient;
use App\Idm\Exception\PersistException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Security\LoginFormAuthenticator;
use App\Security\LoginUser;
use App\Service\EmailService;
use App\Service\TokenService;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class AccountController extends AbstractController
{
    const TOKEN_PW_RESET_STRING = 'pw_reset';
    const TOKEN_MAIL_CONFIRM_STRING = 'confirm_email';

    private IdmManager $manager;
    private IdmRepository $userRepo;
    private EmailService $emailService;
    private TokenService $tokenService;

    public function __construct(IdmManager $manager, EmailService $emailService, TokenService $tokenService)
    {
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->emailService = $emailService;
        $this->tokenService = $tokenService;
    }

    /**
     * @Route("/reset", name="app_reset")
     */
    public function reset(Request $request)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('user_profile');
        }

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['required' => true])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['email'];
            $user = $this->userRepo->findOneBy(['email' => $email]);
            if ($user) {
                try {
                    $token = $this->tokenService->generateToken($user->getUuid(), self::TOKEN_PW_RESET_STRING);
                    $this->emailService->scheduleHook(
                        EmailService::APP_HOOK_RESET_PW,
                        EmailRecipient::fromUser($user),
                        ['token' => $token, 'user' => $user->getUuid()->toString()]
                    );
                } catch (TokenException $e) {
                    // don't show an error here to avoid user enumeration attacks
                    // $this->addFlash('error', 'Zu viele Versuche. Bitte warten.');
                }
            }
            $this->addFlash('success', "Falls {$email} registriert ist, wurde eine E-Mail verschickt");
            return $this->redirectToRoute('app_login');
        }
        return $this->render('security/reset.request.html.twig', [
            'error' => '',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/reset_pw", name="reset_pw")
     */
    public function resetPW(Request $request)
    {
        if (!($user = $this->checkTokenAndGetUser($request, self::TOKEN_PW_RESET_STRING, false))) {
            return $this->redirectToRoute('app_login');
        }
        $fb = $this->createFormBuilder($user)
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Das Passwort muss übereinstimmen.',
                'required' => true,
                'first_options'  => ['label' => 'Passwort'],
                'second_options' => ['label' => 'Password wiederholen'],
            ])
        ;

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->clearToken($request);
            try {
                $this->manager->flush();
            } catch (PersistException $e) {
                $this->addFlash('error', 'Passwort konnte nicht gesetzt werden.');
            }
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset.change.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function clearToken(Request $request)
    {
        $token = strval($request->get('token'));
        $this->tokenService->clearToken($token);
    }

    private function checkTokenAndGetUser(Request $request, string $method, bool $removeToken = false): ?User
    {
        $uuid = strval($request->get('user'));
        $token = strval($request->get('token'));

        if (empty($token) || !TokenService::isValid($token)
            || empty($uuid) || !Uuid::isValid($uuid)) {
            return null;
        }
        $uuid = Uuid::fromString($uuid);
        try {
            $this->tokenService->validateToken($uuid, $method, $token);
        } catch (TokenException $te) {
            $this->addFlash('error', "Token ist abgelaufen oder ungültig.");
            return null;
        }
        if ($removeToken) {
            $this->tokenService->clearToken($token);
        }
        $user = $this->userRepo->findOneById($uuid);
        if (empty($user)) {
            $this->addFlash('error', "User nicht gefunden.");
            return null;
        }
        return $user;
    }

    /**
     * @Route("/resend", name="app_register_resend")
     */
    public function resend(Request $request)
    {
        $email = $request->query->get('email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userRepo->findOneBy(['email' => $email]);
            if (!empty($user) && !$user->getEmailConfirmed()) {
                $this->sendRegisterToken($user);
            }
            $this->addFlash('success', "Falls {$email} registriert ist, wurde eine E-Mail verschickt");
        }
        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/confirm", name="app_register_confirm")
     */
    public function confirm(Request $request, LoginFormAuthenticator $login, GuardAuthenticatorHandler $guard)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('user_profile');
        }
        if (!($user = $this->checkTokenAndGetUser($request, self::TOKEN_MAIL_CONFIRM_STRING, true))) {
            return $this->redirectToRoute('app_login');
        }
        $user->setEmailConfirmed(true);
        $this->manager->flush();
        $this->addFlash('success', "User wurde freigeschalten. Herzlich Willkommen!");
        return $guard->authenticateUserAndHandleSuccess(new LoginUser($user), $request, $login, 'main');
    }

    private function sendRegisterToken(User $user)
    {
        try{
            $token = $this->tokenService->generateToken($user->getUuid(), self::TOKEN_MAIL_CONFIRM_STRING);
        } catch (TokenException $e) {
            // don't show an error here to avoid user enumeration attacks
            // $this->addFlash('error', 'Zu viele Versuche. Bitte warten.');
            return;
        }
        $this->emailService->scheduleHook(
            EmailService::APP_HOOK_REGISTRATION_CONFIRM,
            EmailRecipient::fromUser($user), [
                'token' => $token,
                'user' => $user->getUuid()->toString(),
            ]
        );
    }

    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request)
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
                $this->sendRegisterToken($user);
                $this->addFlash('info', 'Registrierung abgeschlossen. Bestätigungsemail wurde gesendet.');
                return $this->redirectToRoute('app_login');
            } catch (PersistException $e) {
                switch ($e->getCode()) {
                    case PersistException::REASON_NON_UNIQUE:
                        $this->addFlash('error', 'Nickname und/oder E-Mail Adresse schon vergeben');
                        break;
                    default:
                        $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten');
                        break;
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}