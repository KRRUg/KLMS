<?php

namespace App\Controller\Site;

use App\Entity\News;
use App\Repository\NewsRepository;
use App\Service\NewsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/news", name="news")
 */
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
     * @Route("", name="")
     */
    public function index(NewsRepository $repository)
    {
        $news = $this->newsService->getAll();
        return $this->render('site/news/index.html.twig', [
            'news' => $news,
        ]);
    }

    /**
     * @Route("/cards", name="_cards")
     */
    public function cards(Request $request)
    {
        $offset = $request->get('offset', 0);
        $count = $request->get('count', 6);

        $news = $this->newsService->get($offset, $count);
        return $this->render('site/news/_cards.html.twig', [
            'news' => $news,
        ]);
    }

    /**
     * @Route("/news/{id}", name="news")
     * @ParamConverter()
     */
    public function byId(News $news)
    {
        return $this->render('site/news/show.html.twig', [
            'content' => $news,
        ]);
    }
}
