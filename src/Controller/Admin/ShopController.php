<?php

namespace App\Controller\Admin;

use App\Entity\ShopAddon;
use App\Entity\ShopOrder;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Form\ShopAddonType;
use App\Repository\ShopOrderRepository;
use App\Service\ShopService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;


#[IsGranted('ROLE_ADMIN_PAYMENT')]
#[Route(path: '/shop', name: 'shop')]
class ShopController extends AbstractController
{
    private readonly ShopService $shopService;
    private readonly ShopOrderRepository $orderRepository;
    private readonly SerializerInterface $serializer;

    private const CSRF_TOKEN_PAYED = 'shopToken';

    public function __construct(ShopService $shopService, ShopOrderRepository $orderRepository, SerializerInterface $serializer)
    {
        $this->shopService = $shopService;
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response {
        $orders = $this->orderRepository->findAll();

        return $this->render('admin/shop/index.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route(path: '/order/{id}', name:'_edit', methods: ['POST'])]
    public function edit(Request $request, ?ShopOrder $order): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_PAYED, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token presented');
        }

        $action = $request->request->get('action');
        try {
            switch ($action) {
                case 'cancel':
                    $this->shopService->cancelOrder($order);
                    break;
                case 'paid':
                    $this->shopService->setOrderPaid($order);
                    break;
                case 'undo':
                    $this->shopService->setOrderPaidUndo($order);
                    break;
                case 'delete':
                    $this->shopService->deleteOrder($order);
                    break;
                default:
                    $this->addFlash('error', 'Invalid action specified.');
                    return $this->redirectToRoute('admin_shop');
            }
        } catch (OrderLifecycleException $e) {
            $this->addFlash('error', "Aktion konnte nicht durchgeführt werden ({$e->getMessage()}).");
            return $this->redirectToRoute('admin_shop');
        }

        $this->addFlash('success', "Änderung an Order {$order->getId()} erfolgreich.");
        return $this->redirectToRoute('admin_shop');
    }

    #[Route(path: '/order/{id}', name: '_show', methods: ['GET'])]
    public function show(Request $request, ?ShopOrder $order): Response
    {
        if (empty($order)) {
            throw $this->createNotFoundException('Order not found');
        }

        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/shop/show.html.twig', [
            'order' => $order,
            'fulfillable' => $this->shopService->orderAdheresToLimits($order),
            'csrf_token' => self::CSRF_TOKEN_PAYED
        ]);
    }

    #[Route(path: '/addon', name: '_addon', methods: ['GET'])]
    public function indexAddons(): Response
    {
        $addons = $this->shopService->getAddons(all: true);
        return $this->render('admin/shop/addon.html.twig', ['addons' => $addons]);
    }

    #[Route(path: '/addon/new', name:'_addon_new', methods: ['GET', 'POST'])]
    public function newAddon(Request $request): Response
    {
        $form = $this->createForm(ShopAddonType::class, $this->shopService->allocAddon(), [
            'action' => $this->generateUrl('admin_shop_addon_new'),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->shopService->saveAddon($form->getData());
            $this->addFlash('success', "Addon wurde erfolgreich angelegt.");
            return $this->redirectToRoute('admin_shop_addon');
        }
        return $this->render('admin/shop/show_addon.html.twig', ['form' => $form->createView()]);
    }

    #[Route(path: '/addon/{id}', name: '_addon_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editAddon(Request $request, ShopAddon $addon): Response
    {
        $form = $this->createForm(ShopAddonType::class, $addon, [
            'action' => $this->generateUrl('admin_shop_addon_edit', ['id' => $addon->getId()]),
            'method' => 'POST',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->shopService->saveAddon($form->getData());
            $this->addFlash('success', "Änderung an Addon {$addon->getId()} erfolgreich.");
            return $this->redirectToRoute('admin_shop_addon');
        }

        return $this->render('admin/shop/show_addon.html.twig', [
            'addon' => $addon,
            'form' => $form->createView(),
            'csrf_token' => self::CSRF_TOKEN_PAYED
        ]);
    }

    #[Route(path: '/addon/{id}/toggle', name:'_addon_toggle', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function toggleAddon(ShopAddon $addon): Response
    {
        // TODO change to post and add token
        $this->shopService->toggleAddonActivity($addon);

        return $this->redirectToRoute('admin_shop_addon');
    }

    #[Route(path: '/addon/{id}/delete', name:'_addon_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteAddon(Request $request, ShopAddon $addon): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_PAYED, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token presented');
        }
        $this->shopService->deleteAddon($addon);
        $this->addFlash('success', "Addon {$addon->getId()} wurde gelöscht.");
        return $this->redirectToRoute('admin_shop_addon');
    }

    #[Route(path: '/addon/export', name:'_addon_export', methods: ['GET'])]
    public function exportAddon(): Response
    {
        $csvData = [];

        $data = $this->shopService->getAddonOrders();

        foreach ($data as $d) {
            /** @var User $user */
            $user = $d['user'];
            $csvData[] = [
                'uuid' => $user->getUuid()->toString(),
                'nickname' => $user->getNickname(),
                'vorname' => $user->getFirstname(),
                'nachname' => $user->getSurname(),
                'item' => $d['text'],
                'price' => $d['price'],
            ];
        }

        $response = new Response($this->serializer->serialize($csvData, 'csv'));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="addon_orders.csv"');

        return $response;
    }
}