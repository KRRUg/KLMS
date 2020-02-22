<?php

namespace App\Controller\Admin;

use App\Entity\NewsEntry;
use App\Form\NewsEntryType;
use App\Repository\NewsEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NewsAdminController extends AbstractController
{

    /**
     * @Route("/news")
     */
    public function index(NewsEntryRepository $newsEntryRepository) {
        $news = $newsEntryRepository->findAll();
        return $this->render("admin/news/index.html.twig", [
            'news' => $news
        ]);
    }

    /**
     * @Route("/news/new")
     */
    public function new(Request $request) {
        $news = new NewsEntry();
        $form = $this->createForm(NewsEntryType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();
        }

        return $this->render("admin/news/new.html.twig", [
            'form' => $form->createView()
        ]);
    }
}
