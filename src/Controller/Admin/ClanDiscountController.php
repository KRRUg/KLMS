<?php

namespace App\Controller\Admin;

use App\Entity\ClanDiscount;
use App\Service\ClanDiscountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[IsGranted('ROLE_ADMIN_PAYMENT')]
class ClanDiscountController extends AbstractController
{
    private ClanDiscountService $clanDiscountService;
    private \App\Service\UserService $userService;

    public function __construct(ClanDiscountService $clanDiscountService, \App\Service\UserService $userService)
    {
        $this->clanDiscountService = $clanDiscountService;
        $this->userService = $userService;
    }

    #[Route(path: '/clandiscount', name: 'clandiscount', methods: ['GET'])]
    public function index(): Response
    {
        $discounts = $this->clanDiscountService->findAll();
        $clanUuids = array_map(fn($d) => $d->getClanId(), $discounts);
        $clans = $this->userService->getClans($clanUuids, true); // assoziatives Array uuid => Clan

        $discountsWithClanName = array_map(function($discount) use ($clans) {
            $clanName = $clans[$discount->getClanId()->toString()]->getName() ?? $discount->getClanId();
            return [
                'id' => $discount->getId(),
                'clanId' => $discount->getClanId(),
                'clanName' => $clanName,
                'price' => $discount->getPrice(),
            ];
        }, $discounts);

        return $this->render('admin/clandiscount/index.html.twig', [
            'discounts' => $discountsWithClanName,
        ]);
    }


    #[Route(path: '/clandiscount/create', name: 'clandiscount_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $discount = new ClanDiscount();

        $form = $this->createForm(\App\Form\ClanDiscountType::class, $discount, [
            'action' => $this->generateUrl('admin_clandiscount_create'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $discount = $form->getData();
            $this->getDoctrine()->getManager()->persist($discount);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Clan-Discount erfolgreich erstellt!');
            return $this->redirectToRoute('admin_clandiscount');
        }

        return $this->render('admin/clandiscount/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/clandiscount/show/{id}', name: 'clandiscount_show', methods: ['GET'])]
    public function show(ClanDiscount $discount): Response
    {
        return $this->render('admin/clandiscount/show.html.twig', [
            'discount' => $discount,
        ]);
    }

    #[Route(path: '/clandiscount/edit/{id}', name: 'clandiscount_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ClanDiscount $discount): Response
    {
        $form = $this->createForm(\App\Form\ClanDiscountType::class, $discount, [
            'action' => $this->generateUrl('admin_clandiscount_edit', ['id' => $discount->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Clan-Discount erfolgreich bearbeitet!');
            return $this->redirectToRoute('admin_clandiscount');
        }

        return $this->render('admin/clandiscount/edit.html.twig', [
            'form' => $form->createView(),
            'discount' => $discount,
        ]);
    }

    #[Route(path: '/clandiscount/delete/{id}', name: 'clandiscount_delete', methods: ['POST'])]
    public function delete(Request $request, ClanDiscount $discount): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_clandiscount_' . $discount->getId(), $token)) {
            throw $this->createAccessDeniedException('The CSRF token is invalid. ' . $token);
        }

        try {
            $this->clanDiscountService->remove($discount);
            $this->addFlash('success', 'Clan-Discount erfolgreich gelöscht!');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Konnte Clan-Discount nicht löschen.');
        }

        return $this->redirectToRoute('admin_clandiscount');
    }
    
}
