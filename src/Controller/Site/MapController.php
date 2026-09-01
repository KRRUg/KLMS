<?php

namespace App\Controller\Site;

use App\Service\MapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class MapController extends AbstractController
{
    public function __construct(
        private readonly MapService $mapService,
        private readonly string $cartoBasemapApiKey,
    ) {
    }

    #[Route(path: '/map', name: 'map', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('site/map/index.html.twig', [
            'mapPayload' => $this->mapService->getUserMapPayload(),
            'cartoBasemapApiKey' => $this->cartoBasemapApiKey,
        ]);
    }
}
