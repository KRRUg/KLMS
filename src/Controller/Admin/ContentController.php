<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeGeneric;
use App\Form\ContentType;
use App\Repository\ContentRepository;
use App\Repository\NavigationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{

    /**
     * @Route("/content", name="content")
     */
    public function index(Request $request,
                          NavigationRepository $navigationRepository,
                          ContentRepository $contentEntryRepository) {
        $main = $navigationRepository->getRootChildren();
        $id = $request->get('id');

        $current = null;
        if ($id)
            $current = $navigationRepository->find($id);

        $form = null;
        if ($current instanceof NavigationNodeContent) {
            $form = $this->createForm(ContentType::class, $current->getContent())->createView();
        } else if ($current instanceof NavigationNodeGeneric) {
            $defaultData = ['path' => $current->getPath()];
            $form = $this->createFormBuilder($defaultData)
                ->add('path', TextType::class)
                ->add('save', SubmitType::class, ['label' => 'Speichern'])
                ->getForm()->createView();
        }

        return $this->render("admin/content/index.html.twig", [
            'tree' => $main,
            'form' => $form
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
