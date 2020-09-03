<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Form\NewsType;
use App\Service\NewsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    private $newsService;

    /**
     * NewsController constructor.
     * @param $newsService
     */
    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    /**
     * @Route("/news", name="news")
     */
    public function index() {
        $news = $this->newsService->getAll();
        return $this->render("admin/news/index.html.twig", [
            'news' => $news
        ]);
    }

    /**
     * @Route("/news/new", name="news_new")
     */
    public function new(Request $request) {
        $form = $this->createForm(NewsType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->newsService->save($form->getData());
            return $this->redirectToRoute("admin_news");
        }

        return $this->render("admin/news/edit.html.twig", [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/news/delete/{id}", name="news_delete")
     * @ParamConverter()
     */
    public function delete(News $news) {
        $this->newsService->delete($news);
        return $this->redirectToRoute("admin_news");
    }

    /**
     * @Route("/news/edit/{id}", name="news_edit")
     * @ParamConverter()
     */
    public function edit(Request $request, News $news) {
        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->newsService->save($form->getData());
            return $this->redirectToRoute("admin_news");
        }

        return $this->render("admin/news/edit.html.twig", [
            'id' => $news->getId(),
            'form' => $form->createView()
        ]);
    }
}
