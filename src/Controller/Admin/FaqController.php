<?php

namespace App\Controller\Admin;

use App\Entity\Faq;
use App\Form\FaqType;
use App\Service\FaqService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/faq', name: 'faq')]
#[IsGranted('ROLE_ADMIN_CONTENT')]
class FaqController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = 'faqDeleteToken';
    private const CSRF_TOKEN_TOGGLE = 'faqToggleToken';

    private readonly FaqService $faqService;

    public function __construct(FaqService $faqService)
    {
        $this->faqService = $faqService;
    }

    #[Route(path: '/', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $faqs = $this->faqService->getAllFaqs();

        return $this->render('admin/faq/index.html.twig', [
            'faqs' => $faqs,
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
            'csrf_token_toggle' => self::CSRF_TOKEN_TOGGLE,
        ]);
    }

    #[Route(path: '/new', name: '_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $faq = $this->faqService->createFaq();
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->faqService->saveFaq($faq);
                $this->addFlash('success', 'FAQ wurde erfolgreich erstellt.');
                return $this->redirectToRoute('admin_faq');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Fehler beim Erstellen der FAQ: ' . $e->getMessage());
            }
        }

        return $this->render('admin/faq/edit.html.twig', [
            'faq' => $faq,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: '_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Faq $faq): Response
    {
        $form = $this->createForm(FaqType::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->faqService->saveFaq($faq);
                $this->addFlash('success', 'FAQ wurde erfolgreich aktualisiert.');
                return $this->redirectToRoute('admin_faq');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Fehler beim Aktualisieren der FAQ: ' . $e->getMessage());
            }
        }

        return $this->render('admin/faq/edit.html.twig', [
            'faq' => $faq,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/toggle', name: '_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(Request $request, Faq $faq): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_TOGGLE, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->faqService->toggleActive($faq);
            $status = $faq->isActive() ? 'aktiviert' : 'deaktiviert';
            $this->addFlash('success', "FAQ wurde $status.");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Ändern des Status: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_faq');
    }

    #[Route(path: '/{id}/delete', name: '_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Faq $faq): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $this->faqService->deleteFaq($faq);
            $this->addFlash('success', 'FAQ wurde erfolgreich gelöscht.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Löschen der FAQ: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_faq');
    }
}