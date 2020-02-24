<?php

namespace App\Controller\Site;

use App\Repository\ContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    /**
     * @Route("/content", name="content")
     */
    public function index(ContentRepository $contentEntryRepository)
    {
        $content = $contentEntryRepository->findAll();
        return $this->render("site/content/index.html.twig", [
            'contents' => $content
        ]);
    }
}
