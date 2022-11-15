<?php

namespace App\Controller\Site;

use App\Entity\Teamsite;
use App\Service\TeamsiteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TeamsiteController extends AbstractController
{
    private TeamsiteService $service;

    public function __construct(TeamsiteService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/teamsite/{id}", name="teamsite")
     */
    public function byId(Teamsite $teamsite): \Symfony\Component\HttpFoundation\Response
    {
        // warm-up IDM UoW to avoid multiple requests
        $this->service->getUsersOfTeamsite($teamsite);

        return $this->render('site/teamsite/index.html.twig', [
            'teamsite' => $teamsite,
        ]);
    }
}
