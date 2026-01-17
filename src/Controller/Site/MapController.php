<?php

namespace App\Controller\Site;

use App\Service\GeocodingService;
use App\Service\MapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class MapController extends AbstractController
{
    public function __construct(
        private readonly MapService $mapService,
        private readonly GeocodingService $geocodingService,
    ) {
    }

    #[Route(path: '/map', name: 'map', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('site/map/index.html.twig', [
            'map' => $this->mapService->buildUserMap(),
            'centerAddress' => $this->mapService->getCenterAddress(),
            'apiKeyMissing' => !$this->geocodingService->hasApiKey(),
        ]);
    }
}
