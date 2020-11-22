<?php


namespace App\Controller\Admin;


use App\Controller\BaseController;
use App\Entity\Navigation;
use App\Form\NavigationNodeType;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Service\ContentService;
use App\Service\NavigationService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/navigation", name="navigation")
 */
class NavigationController extends BaseController
{
    private $logger;
    private $navService;
    private $contentService;

    private function getAllForms()
    {
        $types = [ new NavigationNodeContent(), new NavigationNodeGeneric(), new NavigationNodeEmpty()];
        $result = [];
        foreach ($types as $type) {
            $result[$type->getType()] = $this->createForm(NavigationNodeType::class, $type)->createView();
        }
        return $result;
    }

    /**
     * NavigationController constructor.
     * @param $logger
     * @param $navService
     * @param $contentService
     */
    public function __construct(NavigationService $navService, ContentService $contentService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->navService = $navService;
        $this->contentService = $contentService;
    }

    /**
     * @Route("", name="", methods={"GET"})
     */
    public function index(Request $request)
    {
        $navs = $this->navService->getAll();
        return $this->render('admin/navigation/index.html.twig', [
            'navs' => $navs,
        ]);
    }

    /**
     * @Route("/edit/{id}.{_format}", name="_edit", defaults={"_format"="html"}, methods={"GET", "POST"})
     * @ParamConverter()
     */
    public function edit(Request $request, Navigation $navigation)
    {
        $array = $this->navService->renderNav($navigation);

        $form = $this->createFormBuilder()
            ->add('navigation', HiddenType::class, [
                'required' => true,
                'data' => json_encode($array),
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode($form->getData()['navigation'], true);
            $success = $this->navService->parseNav($navigation, $array);
            if ($success) {
                $this->addFlash('success', 'Navigation updated');
            } else {
                $this->addFlash('danger', 'Navigation Speichern fehlgeschlagen');
            }
            return $this->redirectToRoute('admin_navigation');
        }
        return $this->render('admin/navigation/edit.html.twig', [
            'navMenu' => $navigation,
            'form' => $form->createView(),
            'typeForms' => $this->getAllForms(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="_delete")
     * @ParamConverter()
     */
    public function delete(Navigation $navigation)
    {
        $this->navService->delete($navigation);
        return $this->redirectToRoute("admin_navigation");
    }

//    /**
//     * @Route(".{_format}", name="", defaults={"_format"="html"}, methods={"GET"})
//     */
//    public function index(Request $request)
//    {
//        $main = $this->navService->getNav();
//        if ($request->getRequestFormat() === 'html') {
//            return $this->render('admin/navigation/index.html.twig', [
//                'tree' => $main,
//            ]);
//        } elseif ($request->getRequestFormat() === 'json') {
//            return $this->apiResponse($this->navService->getNavArray());
//        } else {
//            return $this->createNotFoundException("Unsupported format extension");
//        }
//    }
//
//    /**
//     * @Route("", methods={"POST"})
//     */
//    public function create(Request $request)
//    {
//        $data = json_decode($request->getContent(), true);
//        if ($data === null) {
//            return $this->createBadRequestException();
//        }
//    }
//
//    /**
//     * @Route("/{id}", name="_move", methods={"POST"})
//     * @ParamConverter()
//     */
//    public function move(Request $request, NavigationNode $node)
//    {
//        $data = json_decode($request->getContent(), true);
//        if ($data === null) {
//            return $this->createBadRequestException();
//        }
//        $parent = $data['parent'];
//        $pos = $data['pos'];
//        if (empty($parent) && empty($pos)) {
//            // nothing to do
//            return $this->apiResponse();
//        }
//
//        $parent = empty($parent) ? $node->getParent() : $this->navService->getById($parent);
//        $pos = empty($pos) ? $node->getOrder() : intval($pos);
//        if (empty($pos) || empty($parent)) {
//            return $this->apiError("Invalid pos or parent");
//        }
//
//        $this->navService->moveNode($node, $parent, $pos);
//        return $this->apiResponse();
//    }
//
//    /**
//     * @Route("/{id}", name="_delete", methods={"DELETE"})
//     * @ParamConverter()
//     */
//    public function delete(Request $request, NavigationNode $node)
//    {
//        $this->navService->removeNode($node);
//        return $this->apiResponse([]);
//    }

    //    private $nav;
    //
    //    private $logger;
    //
    //    public function __construct(NavService $nav, LoggerInterface $logger)
    //    {
    //        $this->logger = $logger;
    //        $this->nav = $nav;
    //    }
    //
    //    private function handleForm(?NavigationNode $current, Request $request)
    //    {
    //        if (empty($current))
    //            return null;
    //
    //        $form = $this->createForm(NavigationNodeType::class, $current);
    //
    //        $form->handleRequest($request);
    //        if ($form->isSubmitted() && $form->isValid()) {
    //            $em = $this->getDoctrine()->getManager();
    //            $em->persist($form->getData());
    //            $em->flush();
    //        }
    //
    //        return $form;
    //    }
    //
    //    /**
    //     * @param NavigationNode|null $current
    //     * @param Request $request
    //     * @return int|null Returns null when no action to be done, the node id to redirect to or -1 to redirect without target.
    //     */
    //    private function handleAction(?NavigationNode $current, Request $request)
    //    {
    //        $action = $request->get('action');
    //        $type = $request->get('type');
    //
    //        if (empty($action) || empty($current))
    //            return null;
    //
    //        switch (strtoupper($action)) {
    //            case 'UP':
    //                $this->nav->moveNode($current, true);
    //                return $current->getId();
    //            case 'DOWN':
    //                $this->nav->moveNode($current, false);
    //                return $current->getId();
    //            case 'DELETE':
    //                $this->nav->removeNode($current);
    //                return -1;
    //            case 'ADD':
    //                return $this->nav->newNode($current, $type)->getId();
    //            default:
    //                return $current->getId();
    //        }
    //    }
    //
    //    /**
    //     * @Route("/content", name="content")
    //     */
    //    public function index(Request $request, NavigationRepository $navigationRepository) {
    //
    //        $id = $request->get('id');
    //
    //        $current = null;
    //        if ($id) {
    //            $current = $navigationRepository->find($id);
    //        }
    //
    //        $target = $this->handleAction($current, $request);
    //        if (!empty($target)) {
    //            return $this->redirectToRoute(
    //                "admin_content",
    //                $target < 0 ? [] : ['id' => $target]
    //            );
    //        }
    //
    //        $form = $this->handleForm($current, $request);
    //
    //        $navigationRepository->clear();
    //        $main = $navigationRepository->getRootChildren();
    //
    //        $view = null;
    //        if ($form)
    //            $view = $form->createView();
    //
    //        return $this->render("admin/content/index.html.twig", [
    //            'tree' => $main,
    //            'node' => $current,
    //            'form' => $view
    //        ]);
    //    }

}