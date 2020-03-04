<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{

    /**
     * @Route("/news", name="news")
     */
    public function index(NewsRepository $newsEntryRepository) {
        $news = $newsEntryRepository->findAll();
        return $this->render("admin/content/index.html.twig", [
            'type' => 'News',
            'content' => $news
        ]);
    }

    /**
     * @Route("/news/new", name="news_new")
     */
    public function new(Request $request) {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();
            return $this->redirectToRoute("admin_news");
        }

        return $this->render("admin/content/new.html.twig", [
            'type' => 'News',
            'method' => 'Neu',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/news/delete/{id}", name="news_delete")
     * @ParamConverter()
     */
    public function delete(News $news) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();
        return $this->redirectToRoute("admin_news");
    }

    /**
     * @Route("/news/edit/{id}", name="news_edit")
     * @ParamConverter()
     */
    public function edit(News $news, Request $request) {
        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();
            return $this->redirectToRoute("admin_news");
        }

        return $this->render("admin/content/new.html.twig", [
            'type' => 'News',
            'method' => 'Update',
            'form' => $form->createView()
        ]);
    }
}
