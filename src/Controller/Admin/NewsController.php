<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Form\NewsType;
use App\Service\NewsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/news", name="news")
 * @IsGranted("ROLE_ADMIN_NEWS")
 */
class NewsController extends AbstractController
{
    private const CSRF_TOKEN_DELETE = 'newsDeleteToken';

    private $newsService;

    /**
     * NewsController constructor.
     */
    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    /**
     * @Route("", name="")
     */
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        $news = $this->newsService->getAll();

        return $this->render('admin/news/index.html.twig', [
            'news' => $news,
        ]);
    }

    /**
     * @Route("/new", name="_new")
     */
    public function new(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->createForm(NewsType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->newsService->save($form->getData());

            return $this->redirectToRoute('admin_news');
        }

        return $this->render('admin/news/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     */
    public function delete(Request $request, News $news): \Symfony\Component\HttpFoundation\Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_DELETE, $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid.');
        }

        $this->newsService->delete($news);
        $this->addFlash('success', 'Erfolgreich gelöscht!');

        return $this->redirectToRoute('admin_news');
    }

    /**
     * @Route("/edit/{id}", name="_edit")
     */
    public function edit(Request $request, News $news): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->newsService->save($form->getData());

            return $this->redirectToRoute('admin_news');
        }

        return $this->render('admin/news/edit.html.twig', [
            'form' => $form->createView(),
            'csrf_token_delete' => self::CSRF_TOKEN_DELETE,
        ]);
    }
}
