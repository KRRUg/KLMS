<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/news", name="news")
 * @IsGranted("ROLE_ADMIN_NEWS")
 */
class NewsController extends AbstractController
{
    /**
     * @Route("/", name="")
     */
    public function index(NewsRepository $newsEntryRepository) {
        $news = $newsEntryRepository->findAll();
        return $this->render("admin/news/index.html.twig", [
            'type' => 'News',
            'news' => $news
        ]);
    }

    /**
     * @Route("/new", name="_new")
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

        return $this->render("admin/news/new.html.twig", [
            'type' => 'News',
            'method' => 'Neu',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     * @ParamConverter()
     */
    public function delete(News $news) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();
        return $this->redirectToRoute("admin_news");
    }

    /**
     * @Route("/edit/{id}", name="_edit")
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

        return $this->render("admin/news/new.html.twig", [
            'type' => 'News',
            'method' => 'Update',
            'form' => $form->createView()
        ]);
    }
}
