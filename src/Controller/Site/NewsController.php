<?php

namespace App\Controller\Site;

use App\Entity\News;
use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    /**
     * @Route("/news", name="news_index")
     */
    public function index(NewsRepository $repository)
    {
        $news = $repository->findAll();
        return $this->render('site/content/index.html.twig', [
            'content' => $news[0],
        ]);
    }

    /**
     * @Route("/news/{id}", name="news")
     * @ParamConverter()
     */
    public function byId(News $news)
    {
        return $this->render('site/content/index.html.twig', [
            'content' => $news,
        ]);
    }
}
