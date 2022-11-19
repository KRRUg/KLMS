<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Exception\ServiceException;
use App\Form\ContentType;
use App\Service\ContentService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/content', name: 'content')]
#[IsGranted('ROLE_ADMIN_CONTENT')]
class ContentController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = 'contentDeleteToken';

    private ContentService $contentService;

    /**
     * ContentController constructor.
     */
    public function __construct(ContentService $contentService)
    {
        $this->contentService = $contentService;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $content = $this->contentService->getAll();
        ksort($content);

        return $this->render('admin/content/index.html.twig', [
            'content' => $content,
        ]);
    }

    #[Route(path: '/edit/{id}', name: '_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Content $content): Response
    {
        $form = $this->createForm(ContentType::class, $content);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->contentService->save($form->getData());

            return $this->redirectToRoute('admin_content');
        }

        return $this->render('admin/content/edit.html.twig', [
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }

    #[Route(path: '/new', name: '_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $form = $this->createForm(ContentType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->contentService->save($form->getData());

            return $this->redirectToRoute('admin_content');
        }

        return $this->render('admin/content/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/delete/{id}', name: '_delete')]
    public function delete(Request $request, Content $content): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        try {
            $this->contentService->delete($content);
        } catch (ServiceException $e) {
            switch ($e->getCause()) {
                case ServiceException::CAUSE_IN_USE:
                    $this->addFlash('danger', 'Konnte Content nicht löschen, da in Verwendung.');
                    break;
                case ServiceException::CAUSE_DONT_EXIST:
                    $this->addFlash('warning', 'Content nicht gefunden.');
                    break;
            }
        }

        return $this->redirectToRoute('admin_content');
    }
}
