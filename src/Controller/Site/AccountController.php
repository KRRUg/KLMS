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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class AccountController extends AbstractController
{
    final public const TOKEN_PW_RESET_STRING = 'pw_reset';
    final public const TOKEN_MAIL_CONFIRM_STRING = 'confirm_email';

    private readonly IdmManager $manager;
    private readonly IdmRepository $userRepo;
    private readonly EmailService $emailService;
    private readonly TokenService $tokenService;

    public function __construct(IdmManager $manager, EmailService $emailService, TokenService $tokenService)
    {
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->emailService = $emailService;
        $this->tokenService = $tokenService;
    }

    #[Route(path: '/reset', name: 'app_reset')]
    public function reset(Request $request): Response
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
            $user = $this->userRepo->findOneCiBy(['email' => $email]);
            if ($user) {
                try {
                    $token = $this->tokenService->generateToken($user->getUuid(), self::TOKEN_PW_RESET_STRING);
                    $this->emailService->scheduleHook(
                        EmailService::APP_HOOK_RESET_PW,
                        EmailRecipient::fromUser($user),
                        ['token' => $token, 'user' => $user->getUuid()->toString()]
                    );
                } catch (TokenException) {
                    // don't show an error here to avoid user enumeration attacks
                    // $this->addFlash('error', 'Zu viele Versuche. Bitte warten.');
                }
            }
            $this->addFlash('success', "Falls $email registriert ist, wurde eine E-Mail verschickt");

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset.request.html.twig', [
            'error' => '',
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/reset_pw', name: 'reset_pw')]
    public function resetPW(Request $request): Response
    {
        if (!($user = $this->checkTokenAndGetUser($request, self::TOKEN_PW_RESET_STRING))) {
            return $this->redirectToRoute('app_login');
        }
        $fb = $this->createFormBuilder($user)
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Das Passwort muss übereinstimmen.',
                'required' => true,
                'first_options' => ['label' => 'Passwort'],
                'second_options' => ['label' => 'Password wiederholen'],
            ])
        ;

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->clearToken($request);
            try {
                $this->manager->flush();
            } catch (PersistException) {
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
        } catch (TokenException) {
            $this->addFlash('error', 'Token ist abgelaufen oder ungültig.');

            return null;
        }
        if ($removeToken) {
            $this->tokenService->clearToken($token);
        }
        $user = $this->userRepo->findOneById($uuid);
        if (empty($user)) {
            $this->addFlash('error', 'User nicht gefunden.');

            return null;
        }

        return $user;
    }

    #[Route(path: '/resend', name: 'app_register_resend')]
    public function resend(Request $request): Response
    {
        $email = $request->query->get('email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userRepo->findOneBy(['email' => $email]);
            if (!empty($user) && !$user->getEmailConfirmed()) {
                $this->sendRegisterToken($user);
            }
            $this->addFlash('success', "Falls $email registriert ist, wurde eine E-Mail verschickt");
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/confirm', name: 'app_register_confirm')]
    public function confirm(Request $request, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $login): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('user_profile');
        }
        if (!($user = $this->checkTokenAndGetUser($request, self::TOKEN_MAIL_CONFIRM_STRING, true))) {
            return $this->redirectToRoute('app_login');
        }
        $user->setEmailConfirmed(true);
        $this->manager->flush();
        $this->addFlash('success', 'User wurde freigeschaltet. Herzlich Willkommen!');

        return $userAuthenticator->authenticateUser(new LoginUser($user), $login, $request);
    }

    private function sendRegisterToken(User $user): void
    {
        try {
            $token = $this->tokenService->generateToken($user->getUuid(), self::TOKEN_MAIL_CONFIRM_STRING);
        } catch (TokenException) {
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

    #[Route(path: '/register', name: 'register')]
    public function register(Request $request): Response
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
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
                match ($e->getCode()) {
                    PersistException::REASON_NON_UNIQUE => $this->addFlash('error', 'Nickname und/oder E-Mail Adresse schon vergeben'),
                    default => $this->addFlash('error', 'Es ist ein unerwarteter Fehler aufgetreten'),
                };
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
