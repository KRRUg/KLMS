<?php

namespace App\Controller\Site;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\EMailService;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class EMailController extends AbstractController
{
    private EMailService $mailService;
    private IdmManager $manager;
    private IdmRepository $userRepo;

    public function __construct(EMailService $mailService, IdmManager $manager)
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
        $uuid = Uuid::uuid4();
        switch ($this->mailService->handleToken($token, $uuid)) {
            case 'register':
                $user = $this->userRepo->findOneById($uuid);
                $user->setEmailConfirmed(true);
                $this->manager->flush();
                $this->addFlash('success', "Email Adresse erfolgreich bestätigt.");
                return $this->redirectToRoute('app_login');
            default:
                $this->addFlash('error', "Invalid token supplied.");
                return $this->redirectToRoute('news');
        }
    }
}