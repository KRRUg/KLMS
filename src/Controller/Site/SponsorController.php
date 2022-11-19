<?php

namespace App\Controller\Site;

use App\Service\SponsorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SponsorController extends AbstractController
{
    private readonly SponsorService $service;

    public function __construct(SponsorService $service)
    {
        $this->service = $service;
    }

    /**
     * @Route("/sponsor", name="sponsor")
     */
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        if (!$this->service->active()) {
            throw $this->createNotFoundException();
        }

        $categories = $this->service->getCategories();

        return $this->render('site/sponsor/index.html.twig', [
            'categories' => $categories,
        ]);
    }
}
