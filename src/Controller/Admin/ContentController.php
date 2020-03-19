<?php

namespace App\Controller\Admin;

use App\Entity\Content;
use App\Entity\NavigationNode;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Form\ContentType;
use App\Repository\ContentRepository;
use App\Repository\NavigationRepository;
use App\Service\NavService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    private $nav;

    public function __construct(NavService $nav)
    {
        $this->nav = $nav;
    }

    private function handleForm(?NavigationNode $current, Request $request)
    {
        if (empty($current))
            return null;

        $em = $this->getDoctrine()->getManager();
        $form = null;
        if ($current instanceof NavigationNodeContent) {
            $form = $this->createForm(ContentType::class, $current->getContent());
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $content = $form->getData();
                $em->persist($content);
                $em->flush();
            }
        } else if ($current instanceof NavigationNodeGeneric) {
            $defaultData = ['path' => $current->getPath()];
            $form = $this->createFormBuilder($defaultData)
                ->add('path', TextType::class)
                ->add('save', SubmitType::class, ['label' => 'Speichern'])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $current->setPath($form->getData()['path']);
                $em->persist($current);
                $em->flush();
            }
        }
        return $form;
    }

    private function handleAction(?NavigationNode $current, Request $request)
    {
        $action = $request->get('action');
        $type = $request->get('type');
        if (empty($action) || empty($current))
            return $current;

        switch (strtoupper($action)) {
            case 'UP':
                $this->nav->moveNode($current, true);
                return $current;
            case 'DOWN':
                $this->nav->moveNode($current, false);
                return $current;
            case 'DELETE':
                $this->nav->removeNode($current);
                return null;
            case 'ADD':
                return $this->nav->newNode($current, $type);
        }
    }

    /**
     * @Route("/content", name="content")
     */
    public function index(Request $request,
                          NavigationRepository $navigationRepository,
                          ContentRepository $contentEntryRepository) {

        $id = $request->get('id');

        $current = null;
        if ($id)
            $current = $navigationRepository->find($id);

        $current = $this->handleAction($current, $request);
        $form = $this->handleForm($current, $request);

        $navigationRepository->clear();
        $main = $navigationRepository->getRootChildren();

        $view = null;
        if ($form)
            $view = $form->createView();

        return $this->render("admin/content/index.html.twig", [
            'tree' => $main,
            'node' => $current,
            'form' => $view
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
}
