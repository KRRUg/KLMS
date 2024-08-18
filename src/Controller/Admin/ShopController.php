<?php

namespace App\Controller\Admin;

use App\Entity\ShopOrder;
use App\Exception\OrderLifecycleException;
use App\Idm\IdmRepository;
use App\Repository\ShopOrderRepository;
use App\Service\ShopService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


#[IsGranted('ROLE_ADMIN_PAYMENT')]
#[Route(path: '/shop', name: 'shop')]
class ShopController extends AbstractController
{
    private ShopService $shopService;
    private ShopOrderRepository $orderRepository;
    private IdmRepository $userRepo;

    private const CSRF_TOKEN_PAYED = 'shopToken';

    public function __construct(ShopService $shopService, ShopOrderRepository $orderRepository)
    {
        $this->shopService = $shopService;
        $this->orderRepository = $orderRepository;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response {
        // TODO warm-up IDM cache with all users in orders

        $orders = $this->orderRepository->findAll();

        return $this->render('admin/shop/index.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route(path: '/{id}', name:'_edit', methods: ['POST'])]
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

    #[Route(path: '/{id}', name: '_show', methods: ['GET'])]
    public function show(Request $request, ?ShopOrder $order): Response
    {
        if (empty($order)) {
            throw $this->createNotFoundException('Order not found');
        }

        if (!$request->isxmlhttprequest()) {
            throw $this->createnotfoundexception();
        }

        return $this->render('admin/shop/show.html.twig', [
            'order' => $order,
            'csrf_token' => self::CSRF_TOKEN_PAYED
        ]);
    }
}