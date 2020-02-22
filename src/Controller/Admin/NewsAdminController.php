<?php

namespace App\Controller\Admin;

use App\Entity\NewsEntry;
use App\Form\NewsEntryType;
use App\Repository\NewsEntryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;

class NewsAdminController extends AbstractController
{

    /**
     * @Route("/news", name="news")
     */
    public function index(NewsEntryRepository $newsEntryRepository) {
        $news = $newsEntryRepository->findAll();
        return $this->render("admin/news/index.html.twig", [
            'news' => $news
        ]);
    }

    /**
     * @Route("/news/new", name="news_new")
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
            return $this->redirectToRoute("admin_news");
        }

        return $this->render("admin/news/new.html.twig", [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/news/delete/{id}", name="news_delete")
     * @ParamConverter()
     */
    public function delete(NewsEntry $news) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();
        return $this->redirectToRoute("admin_news");
    }

    /**
     * @Route("/news/edit/{id}", name="news_edit")
     * @ParamConverter()
     */
    public function edit(NewsEntry $news, Request $request) {
        $form = $this->createForm(NewsEntryType::class, $news);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();
            return $this->redirectToRoute("admin_news");
        }

        return $this->render("admin/news/new.html.twig", [
            'form' => $form->createView()
        ]);
    }
}
