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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/content", name="content")
 * @IsGranted("ROLE_ADMIN_CONTENT")
 */
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

    /**
     * @param NavigationNode|null $current
     * @param Request $request
     * @return int|null Returns null when no action to be done, the node id to redirect to or -1 to redirect without target.
     */
    private function handleAction(?NavigationNode $current, Request $request)
    {
        $action = $request->get('action');
        $type = $request->get('type');

        if (empty($action) || empty($current))
            return null;

        switch (strtoupper($action)) {
            case 'UP':
                $this->nav->moveNode($current, true);
                return $current->getId();
            case 'DOWN':
                $this->nav->moveNode($current, false);
                return $current->getId();
            case 'DELETE':
                $this->nav->removeNode($current);
                return -1;
            case 'ADD':
                return $this->nav->newNode($current, $type)->getId();
            default:
                return $current->getId();
        }
    }

    /**
     * @Route("/", name="")
     */
    public function index(Request $request, NavigationRepository $navigationRepository) {

        $id = $request->get('id');

        $current = null;
        if ($id) {
            $current = $navigationRepository->find($id);
        }

        $target = $this->handleAction($current, $request);
        if (!empty($target)) {
            return $this->redirectToRoute(
                "admin_content",
                $target < 0 ? [] : ['id' => $target]
            );
        }

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
