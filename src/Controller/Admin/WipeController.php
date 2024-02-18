<?php

namespace App\Controller\Admin;

use App\Service\WipeService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/wipe', name: 'wipe')]
#[IsGranted('ROLE_ADMIN_SUPER')]
class WipeController extends AbstractController
{
    private readonly WipeService $wipeService;

    public function __construct(WipeService $wipeService)
    {
        $this->wipeService = $wipeService;
    }

    static private function serviceId2Name(string $serviceId): string
    {
        $id = explode('\\', $serviceId);
        $id = end($id);
        return str_replace("Service", "", $id);
    }

    #[Route(path: '', name: '')]
    public function index(Request $request): Response
    {
        $serviceIds = $this->wipeService->getWipeableServiceIds();

        $fb = $this->createFormBuilder();
        $toFriendlyId = fn($id) => str_replace('\\', '-', $id);
        foreach ($serviceIds as $serviceId) {
            $fb->add($toFriendlyId($serviceId), CheckboxType::class, [
                'label' => self::serviceId2Name($serviceId),
                'required' => false,
                'attr' => ['data-dependency' => implode(',', array_map($toFriendlyId, $this->wipeService->getAllDependenciesOfService($serviceId)))],
            ]);
        }
        $form = $fb->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $selected = [];
            foreach ($serviceIds as $serviceId) {
                if ($form->get($toFriendlyId($serviceId))->getData()) {
                    $selected[] = $serviceId;
                }
            }
            if (empty($selected)) {
                $this->addFlash("warning", "Kein Service ausgewählt.");
            } elseif (!$this->wipeService->wipe($selected)) {
                $this->addFlash("error", "Wipe configuration invalid.");
            } else {
                $this->addFlash("success", "Wipe war erfolgreich.");
                return $this->redirectToRoute('admin_dashboard');
            }
        }

        return $this->render('admin/wipe/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
