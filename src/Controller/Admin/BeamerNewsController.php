<?php

namespace App\Controller\Admin;

use App\Entity\BeamerNews;
use App\Form\BeamerNewsType;
use App\Service\BeamerNewsService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/beamer', name: 'beamer')]
#[IsGranted('ROLE_ADMIN_NEWS')]
class BeamerNewsController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = 'beamerNewsDeleteToken';

    private readonly BeamerNewsService $beamerNewsService;

    public function __construct(BeamerNewsService $beamerNewsService)
    {
        $this->beamerNewsService = $beamerNewsService;
    }

    #[Route(path: '', name: '')]
    public function index(): Response
    {
        $news = $this->beamerNewsService->getAll();

        return $this->render('admin/beamernews/index.html.twig', [
            'news' => $news,
        ]);
    }

    #[Route(path: '/new', name: '_new')]
    public function new(Request $request): Response
    {
        $form = $this->createForm(BeamerNewsType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->beamerNewsService->save($form->getData());

            return $this->redirectToRoute('admin_beamer');
        }

        return $this->render('admin/beamernews/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/delete/{id}', name: '_delete')]
    public function delete(Request $request, BeamerNews $beamerNews): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        $this->beamerNewsService->delete($beamerNews);
        $this->addFlash('success', 'Erfolgreich gelöscht!');

        return $this->redirectToRoute('admin_beamer');
    }

    #[Route(path: '/edit/{id}', name: '_edit')]
    public function edit(Request $request, BeamerNews $beamerNews): Response
    {
        $form = $this->createForm(BeamerNewsType::class, $beamerNews);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->beamerNewsService->save($form->getData());

            return $this->redirectToRoute('admin_beamer');
        }

        return $this->render('admin/beamernews/edit.html.twig', [
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }
}
