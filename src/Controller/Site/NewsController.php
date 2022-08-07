<?php

namespace App\Controller\Site;

use App\Entity\Clan;
use App\Entity\News;
use App\Entity\Seat;
use App\Entity\User;
use App\Entity\UserGamer;
use App\Idm\IdmManager;
use App\Repository\NewsRepository;
use App\Service\NewsService;
use App\Service\UserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/news", name="news")
 */
class NewsController extends AbstractController
{
    private NewsService $newsService;
    private UserService $userService;

    private const PRELOAD_NEWS_CNT = 6;

    /**
     * NewsController constructor.
     * @param $newsService
     */
    public function __construct(NewsService $newsService, UserService $userService)
    {
        $this->newsService = $newsService;
        $this->userService = $userService;
    }

    /**
     * @Route("", name="")
     */
    public function index(Request $request)
    {
        $cnt = intval($request->get('cnt', 0));
        $cnt = max($cnt, NewsController::PRELOAD_NEWS_CNT);
        $news = $this->newsService->get(0, $cnt);
        $total = $this->newsService->count();
        $this->userService->preloadUsers(array_map(function (News $news) { return $news->getAuthorId(); }, $news));
        return $this->render('site/news/index.html.twig', [
            'news' => $news,
            'news_total_cnt' => $total,
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
     * @Route("/{id}", requirements={"id"="\d+"}, name="_detail")
     * @ParamConverter()
     */
    public function byId(News $news)
    {
        if (!$news->isActive())
            throw $this->createNotFoundException();

        return $this->render('site/news/show.html.twig', [
            'content' => $news,
        ]);
    }
}
