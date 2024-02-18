<?php

namespace App\Controller\Admin;

use App\Service\WipeService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/wipe', name: 'wipe')]
#[IsGranted('ROLE_ADMIN_SUPER')]
class WipeController extends AbstractController
{
    private readonly WipeService $wipeService;

    public function __construct(WipeService $wipeService)
    {
        $this->wipeService = $wipeService;
    }

    #[Route(path: '', name: '')]
    public function index(): Response
    {
        $serviceNames = $this->wipeService->getWipeableServiceIds();

        return $this->render('admin/wipe/index.html.twig', [
            'services' => $serviceNames,
        ]);
    }
}
