<?php

namespace App\Controller\Site;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    /**
     * @Route("/content", name="content")
     */
    public function index()
    {
        return $this->render('site/content/index.html.twig', [
            'controller_name' => 'contentController',
        ]);
    }
}
