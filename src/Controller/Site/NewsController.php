<?php

namespace App\Controller\Site;

use App\Entity\Clan;
use App\Entity\News;
use App\Idm\IdmManager;
use App\Repository\NewsRepository;
use App\Service\NewsService;
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
     * @Route("/test", name="test")
     */
    public function test(Request $request, IdmManager $idmManager)
    {
        $uuid = Uuid::fromInteger(1001)->toString();
        $repo = $idmManager->getRepository(Clan::class);
        $clan = $repo->findOneById($uuid);

//        $n1 = $repository->findOneBy(['id' => 1]);
//        $n1->setTitle("fup");
//        $n2 = $repository->findOneBy(['id' => 1]);
//        $em = $this->get('doctrine')->getManager();
//        $em->remove($n1);
//        $em->flush();
        $name = $clan->getName();
        $admin = $clan->getAdmins()[0]->getNickname();
        $users = [];
        foreach ($clan->getUsers() as $user) {
            $users[] = $user->getNickname();
        }
//        $users = array_map(function ($user) { return $user->getNickname(); }, iterator_to_array($clan->getUsers()));
        $users = implode(", ", $users);

        return new Response("<body><h1>Hallo, {$name}</h1><p>Admin: {$admin}<br>Users: {$users}</p></body>");
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
