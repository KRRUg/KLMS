<?php

namespace App\Controller\Site;

use App\Entity\Tourney;
use App\Service\TourneyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TourneyController extends AbstractController
{
    private readonly TourneyService $service;

    public function __construct(TourneyService $service)
    {
        $this->service = $service;
    }

    #[Route(path: '/tourney', name: 'tourney')]
    public function index(): Response
    {
        $tourneys = $this->service->getVisibleTourneys();

        return $this->render('site/tourney/index.html.twig', [
            'tourneys' => $tourneys,
        ]);
    }

    #[Route(path: '/tourney/{id}', name: 'tourney_show')]
    public function byId(Tourney $tourney): Response
    {
        return $this->render('site/tourney/show.html.twig', [
            'tourney' => $tourney,
        ]);
    }
}