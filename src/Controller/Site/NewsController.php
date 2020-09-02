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

    private const PRELOAD_NEWS_CNT = 6;

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
        $news = $this->newsService->get(0, NewsController::PRELOAD_NEWS_CNT);
        $news_cnt = $this->newsService->count();
        return $this->render('site/news/index.html.twig', [
            'news' => $news,
            'news_total_cnt' => $news_cnt,
        ]);
    }

    /**
     * @Route("/cards", name="_cards")
     */
    public function cards(Request $request)
    {
        $offset = $request->get('offset', NewsController::PRELOAD_NEWS_CNT);
        $count = $request->get('count', 4);

        $news = $this->newsService->get($offset, $count);
        return $this->render('site/news/_cards.html.twig', [
            'news' => $news,
        ]);
    }

    /**
     * @Route("/{id}", name="_detail")
     * @ParamConverter()
     */
    public function byId(News $news)
    {
        return $this->render('site/news/show.html.twig', [
            'content' => $news,
        ]);
    }
}
