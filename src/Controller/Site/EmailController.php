<?php

namespace App\Controller\Site;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EmailController extends AbstractController
{
    private EmailService $mailService;
    private IdmManager $manager;
    private IdmRepository $userRepo;

    public function __construct(EmailService $mailService, IdmManager $manager)
    {
        $this->mailService = $mailService;
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * @Route("/email", name="email_token")
     */
    public function handle_token(Request $request)
    {
        $token = strval($request->get('token', ""));
        if (empty($token)) {
            return $this->redirectToRoute('news');
        }
        $token = $this->mailService->handleToken($token, $uuid);
        $user = $this->userRepo->findOneById($uuid);
        if (empty($token) || empty($user)) {
            $this->addFlash('error', "Invalid token supplied.");
        } else {
            switch ($token) {
                case 'register':
                    $user->setEmailConfirmed(true);
                    $this->addFlash('success', "Email Adresse {$user->getEmail()} erfolgreich bestätigt.");
                    break;
                case 'unsubscribe':
                    $user->setInfoMails(false);
                    $this->addFlash('success', "Newsletter für {$user->getEmail()} wurde abbestellt.");
                    break;
                default:
                    $this->addFlash('error', "Invalid token supplied.");
                    break;
            }
        }
        $this->manager->flush();
        return $this->redirectToRoute('news');
    }
}