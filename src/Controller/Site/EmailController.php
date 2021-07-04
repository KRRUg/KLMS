<?php

namespace App\Controller\Site;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\EmailService;
use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EmailController extends AbstractController
{
    private EmailService $mailService;
    private TokenService $tokenService;
    private IdmManager $manager;
    private IdmRepository $userRepo;

    public function __construct(EmailService $mailService, TokenService $tokenService, IdmManager $manager)
    {
        $this->mailService = $mailService;
        $this->tokenService = $tokenService;
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * @Route("/unsubscribe", name="email_unsubscribe")
     */
    public function unsubscribe(Request $request)
    {
        $token = strval($request->get('token', ""));
        if (empty($token)) {
            return $this->redirectToRoute('news');
        }
        $uuid = $this->mailService->handleUnsubscribeToken($token);
        if (empty($uuid)) {
            $this->addFlash('error', "Ungültigen Token übermittelt.");
            return $this->redirectToRoute('news');
        }
        $user = $this->userRepo->findOneById($uuid);
        if (empty($user)) {
            $this->addFlash('error', "User nicht gefunden.");
            return $this->redirectToRoute('news');
        }
        $user->setInfoMails(false);
        $this->manager->flush();
        $this->addFlash('success', "Newsletter für {$user->getEmail()} wurde abbestellt.");
        return $this->redirectToRoute('app_login');
    }
}