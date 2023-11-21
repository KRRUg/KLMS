<?php

namespace App\Controller\Site;

use App\Entity\SponsorCategory;
use App\Service\SettingService;
use App\Service\SponsorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/sponsor', name: 'sponsor')]
class SponsorController extends AbstractController
{
    private readonly SponsorService $service;
    private readonly SettingService $settings;

    public function __construct(SponsorService $service, SettingService $settings)
    {
        $this->service = $service;
        $this->settings = $settings;
    }

    #[Route(path: '', name: '')]
    public function index(): Response
    {
        if (!$this->service->active()) {
            throw $this->createNotFoundException();
        }

        $categories = $this->service->getCategories();
        if (!$this->settings->get('sponsor.page.show_empty', false)) {
            $categories = array_filter($categories, fn(SponsorCategory $c) => $c->getSponsors()->count() > 0);
        }

        return $this->render('site/sponsor/index.html.twig', [
            'categories' => $categories,
        ]);
    }
}
