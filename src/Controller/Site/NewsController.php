<?php

namespace App\Controller\Site;

use App\Entity\News;
use App\Service\NewsService;
use App\Service\UserService;
use Eko\FeedBundle\Feed\FeedManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/news', name: 'news')]
class NewsController extends AbstractController
{
    private readonly NewsService $newsService;
    private readonly UserService $userService;
    private readonly FeedManager $feedManager;

    private const PRELOAD_NEWS_CNT = 6;

    /**
     * NewsController constructor.
     */
    public function __construct(NewsService $newsService, UserService $userService, FeedManager $feedManager)
    {
        $this->newsService = $newsService;
        $this->userService = $userService;
        $this->feedManager = $feedManager;
    }

    #[Route(path: '', name: '')]
    public function index(Request $request): Response
    {
        $cnt = intval($request->get('cnt', 0));
        $cnt = max($cnt, NewsController::PRELOAD_NEWS_CNT);
        $news = $this->newsService->get(0, $cnt);
        $total = $this->newsService->count();
        $this->userService->preloadUsers(array_map(fn (News $news) => $news->getAuthorId(), $news));

        return $this->render('site/news/index.html.twig', [
            'news' => $news,
            'news_total_cnt' => $total,
        ]);
    }

    #[Route(path: '/cards', name: '_cards')]
    public function cards(Request $request): Response
    {
        $offset = $request->get('offset', NewsController::PRELOAD_NEWS_CNT);
        $count = $request->get('count', 4);

        $news = $this->newsService->get($offset, $count);

        return $this->render('site/news/_cards.html.twig', [
            'news' => $news,
        ]);
    }

    #[Route(path: '/{id}', requirements: ['id' => '\d+'], name: '_detail')]
    public function byId(News $news): Response
    {
        if (!$news->isActive()) {
            throw $this->createNotFoundException();
        }

        return $this->render('site/news/show.html.twig', [
            'content' => $news,
        ]);
    }

    #[Route(path: '/feed.rss', name: '_feed')]
    public function feed(): Response
    {
        $news = $this->newsService->get();
        $news = array_map(NewsService::toFeedElement(...), $news);
        $feed = $this->feedManager->get('news');
        $feed->addFromArray($news);
        return new Response($feed->render('rss'), 200, ['Content-Type' => 'application/rss+xml']);
    }
}
