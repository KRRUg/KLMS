<?php

namespace App\Controller\Site;

use App\Entity\Content;
use App\Repository\ContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{

    /**
     * @Route("/content", name="content_index")
     */
    public function index(ContentRepository $repository)
    {
        $content = $repository->findAll();
        return $this->render('site/content/index.html.twig', [
            'content' => $content[0],
        ]);
    }

    /**
     * @Route("/content/{id}", name="content")
     * @ParamConverter()
     */
    public function byId(Content $content)
    {
        return $this->render('site/content/index.html.twig', [
            'content' => $content,
        ]);
    }
}
