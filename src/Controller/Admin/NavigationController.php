<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\Navigation;
use App\Entity\NavigationNodeTeamsite;
use App\Form\NavigationNodeType;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Service\NavigationService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/navigation", name="navigation")
 */
class NavigationController extends BaseController
{
    private LoggerInterface $logger;
    private NavigationService $navService;

    private function getAllForms(): array
    {
        $types = [ new NavigationNodeEmpty(), new NavigationNodeContent(), new NavigationNodeGeneric(), new NavigationNodeTeamsite() ];
        $result = [];
        foreach ($types as $type) {
            $result[$type->getType()] = $this->createForm(NavigationNodeType::class, $type)->createView();
        }
        return $result;
    }

    public function __construct(NavigationService $navService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->navService = $navService;
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
     */
    public function edit(Request $request, Navigation $navigation)
    {
        $array = $this->navService->renderNav($navigation);

        $form = $this->createFormBuilder()
            ->add('navigation', HiddenType::class, [
                'required' => true,
                'data' => json_encode($array),
                'constraints' => [new Assert\Json()],
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
}
