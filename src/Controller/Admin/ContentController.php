<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Form\ContentType;
use App\Repository\ContentRepository;
use App\Repository\NavigationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{

    /**
     * @Route("/content", name="content")
     */
    public function index(NavigationRepository $navigationRepository,
                          ContentRepository $contentEntryRepository) {
        $main = $navigationRepository->getRootChildren();
        $content = $contentEntryRepository->findAll();
        return $this->render("admin/content/index.html.twig", [
            'tree' => $main,
            'content' => $content
        ]);
    }

    /**
     * @Route("/content/new", name="content_new")
     */
    public function new(Request $request) {
        $content = new Content();
        $form = $this->createForm(ContentType::class, $content);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($content);
            $em->flush();
            return $this->redirectToRoute("admin_content");
        }

        return $this->render("admin/content/new.html.twig", [
            'method' => 'Neu',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/content/delete/{id}", name="content_delete")
     * @ParamConverter()
     */
    public function delete(Content $content) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($content);
        $em->flush();
        return $this->redirectToRoute("admin_content");
    }

    /**
     * @Route("/content/edit/{id}", name="content_edit")
     * @ParamConverter()
     */
    public function edit(Content $content, Request $request) {
        $form = $this->createForm(ContentType::class, $content);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $content = $form->getData();
            $em = $this->getDoctrine()->getManager();
            $em->persist($content);
            $em->flush();
            return $this->redirectToRoute("admin_content");
        }

        return $this->render("admin/content/new.html.twig", [
            'method' => 'Bearbeiten',
            'form' => $form->createView()
        ]);
    }
}
