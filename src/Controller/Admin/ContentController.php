<?php

namespace App\Controller\Admin;

use App\Entity\NavigationNode;
use App\Form\NavigationNodeType;
use App\Repository\ContentRepository;
use App\Repository\NavigationRepository;
use App\Service\NavService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    private $nav;

    private $logger;

    public function __construct(NavService $nav, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->nav = $nav;
    }

    private function handleForm(?NavigationNode $current, Request $request)
    {
        if (empty($current))
            return null;

        $form = $this->createForm(NavigationNodeType::class, $current);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($form->getData());
            $em->flush();
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
}
