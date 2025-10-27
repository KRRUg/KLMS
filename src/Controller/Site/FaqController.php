<?php

namespace App\Controller\Site;

use App\Service\FaqService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/faq', name: 'faq')]
class FaqController extends AbstractController
{
    private readonly FaqService $faqService;

    public function __construct(FaqService $faqService)
    {
        $this->faqService = $faqService;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $searchTerm = $request->query->get('search', '');
        
        if (!empty($searchTerm)) {
            $faqs = $this->faqService->searchFaqs($searchTerm);
        } else {
            $faqs = $this->faqService->getActiveFaqs();
        }

        return $this->render('site/faq/index.html.twig', [
            'faqs' => $faqs,
            'searchTerm' => $searchTerm,
        ]);
    }
}