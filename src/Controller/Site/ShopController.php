<?php

namespace App\Controller\Site;

use App\Entity\ShopAddon;
use App\Entity\ShopOrder;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Form\CheckoutType;
use App\Service\SettingService;
use App\Service\ShopService;
use App\Service\TicketService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
#[Route(path: '/shop', name: 'shop')]
class ShopController extends AbstractController
{
    private readonly TicketService $ticketService;
    private readonly ShopService $shopService;
    private readonly SettingService $settingService;
    private readonly LoggerInterface $logger;

    public function __construct(TicketService       $ticketService,
                                ShopService         $shopService,
                                SettingService      $settingService,
                                LoggerInterface     $logger
    ){
        $this->ticketService = $ticketService;
        $this->shopService = $shopService;
        $this->settingService = $settingService;
        $this->logger = $logger;
    }

    private const CSRF_TOKEN_CANCEL = 'cancelOrder';

    // create an object to only query it once
    private function maxCountAddon(ShopAddon $addon): ?int
    {
        if ($addon->getOnlyOnce() && $this->shopService->countOrderedAddonByUser($addon, $this->getUser()->getUser()) > 0) {
            return -1;
        }
        if (!is_null($addon->getMaxQuantityGlobal())) {
            $boughtGlobal = $this->shopService->countOrderedAddon($addon);
            return max(0, $addon->getMaxQuantityGlobal() - $boughtGlobal);
        }
        return null;
    }

    #[Route(path: '/checkout', name: '_checkout')]
    public function checkout(Request $request): Response
    {
        if (!$this->settingService->get('lan.signup.enabled', false)) {
            $this->addFlash('warning', "Anmeldung ist noch nicht freigeschalten.");
            return $this->redirect('/');
        }

        /** @var User $user */
        $user = $this->getUser()->getUser();
        $orders = $this->shopService->getOrderByUser($user);
        $open_order = array_filter($orders, function (ShopOrder $o) { return $o->isOpen(); });

        if (count($open_order) > 0) {
            $this->logger->warning("User {$user->getUuid()} has multiple open orders.");
        }

        if (!empty($open_order)) {
            $this->addFlash('warning', "Es besteht eine offene Bestellung. Diese zuerst bezahlen oder stornieren.");
            return $this->redirectToRoute('shop_orders', ['show' => $open_order[0]->getId()]);
        }

        $addons = $this->shopService->getAddons();
        $userRegistered = $this->ticketService->isUserRegistered($user);
        $form = $this->createForm(CheckoutType::class, options: [
            'code' => !$userRegistered,
            'addons' => $addons,
            'max_addon_count_callback' => $this->maxCountAddon(...),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // add tickets to order
            $noTickets = intval($data['tickets'] ?? 0);
            $order = $this->shopService->allocOrder($user);
            $this->shopService->orderAddTickets($order, $noTickets);

            // add addons to order
            foreach ($addons as $addon) {
                $cnt = $data['addon'.$addon->getId()] ?? 0;
                $this->shopService->orderAddAddon($order, $addon, $cnt);
            }

            // handle code
            $ticketActivation = false;
            $code = $data['code'] ?? '';
            if ($this->ticketService->ticketCodeUnused($code)) {
                $ticketActivation = true;
                if ($this->ticketService->redeemTicket($code, $user)) {
                    $this->addFlash('success', 'Ticket erfolgreich aktiviert.');
                } else {
                    $this->addFlash('error', 'Ticket konnte nicht aktiviert werden.');
                }
            }

            if (!$order->isEmpty()) {
                $this->shopService->placeOrder($order);
                $this->addFlash('success', "Order erfolgreich angelegt.");
                return $this->redirectToRoute('shop_orders');
            } else if(!$ticketActivation) {
                $this->addFlash('warning', "Leere Bestellung kann nicht angelegt werden.");
            }

            return $this->redirect('/');
        }

        // show order dialog
        return $this->render('site/shop/checkout.html.twig', [
            'form' => $form->createView(),
            'addons' => $addons,
            'has_ticket' => $userRegistered,
        ]);
    }

    #[Route(path: '/check', name: '_check')]
    public function checkCode(Request $request): JsonResponse
    {
        $code = $request->get('code', "");
        $result = $this->ticketService->ticketCodeUnused($code);
        return $this->json(['result' => $result]);
    }

    #[Route(path: '/orders', name: '_orders', methods: ['GET', 'POST'])]
    public function orders(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser()->getUser();
        $orders = $this->shopService->getOrderByUser($user);

        if ($request->getMethod() == 'POST') {
            $token = $request->request->get('_token');
            $action = $request->request->get('action');
            $id = $request->request->get('order-id');

            if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_CANCEL, $token)) {
                throw $this->createAccessDeniedException('Invalid CSRF token presented');
            }
            // check if the order is by the user
            $order = array_filter($orders, function (ShopOrder $order) use ($id) { return $order->getId() == $id; });
            if (empty($order)) {
                throw $this->createAccessDeniedException('Invalid order specified.');
            }
            $order = array_pop($order);
            try {
                switch ($action) {
                    case 'cancel':
                        $this->shopService->cancelOrder($order);
                        break;
                    default:
                        $this->addFlash('error', "Invalid action specified.");
                        return $this->redirectToRoute('shop_orders');
                }
                $this->addFlash('success', "Bestellung #{$order->getId()} wurde storniert.");
            } catch (OrderLifecycleException $e) {
                $this->addFlash('error', "Bestellung #{$order->getId()} konnte nicht geändert werden.");
            }
        }

        // show open order with option to cancel
        return $this->render('site/shop/orders.html.twig', [
            'orders' => $orders,
            'csrf_token_cancel' => self::CSRF_TOKEN_CANCEL,
        ]);
    }
}
