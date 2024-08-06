<?php

namespace App\Controller\Site;

use App\Entity\ShopOrder;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Form\CheckoutType;
use App\Repository\ShopOrderRepository;
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
class LanSignupController extends AbstractController
{
    private readonly TicketService $ticketService;
    private readonly ShopService $shopService;
    private readonly ShopOrderRepository $shopOrderRepository;
    private readonly SettingService $settingService;
    private readonly LoggerInterface $logger;

    public function __construct(TicketService       $ticketService,
                                ShopService         $shopService,
                                ShopOrderRepository $shopOrderRepository,
                                SettingService      $settingService,
                                LoggerInterface     $logger
    ){
        $this->ticketService = $ticketService;
        $this->shopService = $shopService;
        $this->settingService = $settingService;
        $this->logger = $logger;
        $this->shopOrderRepository = $shopOrderRepository;
    }

    private const CSRF_TOKEN_CANCEL = 'cancelOrder';

    #[Route(path: '/checkout', name: '_checkout')]
    public function checkout(Request $request): Response
    {
        if (!$this->settingService->isSet('lan.signup.enabled')) {
            $this->addFlash('warning', "Anmeldung ist noch nicht freigeschalten.");
            return $this->redirect('/');
        }

        /** @var User $user */
        $user = $this->getUser()->getUser();
        $orders = $this->shopOrderRepository->queryOrders($user->getUuid());
        $open_order = array_filter($orders, function (ShopOrder $o) { return $o->isOpen(); });

        if (count($open_order) > 0) {
            $this->logger->warning("User {$user->getUuid()} has multiple open orders.");
        }

        if (!empty($open_order)) {
            $this->addFlash('warning', "Es besteht eine offene Bestellung. Diese zuerst bezahlen oder stornieren.");
            return $this->redirectToRoute('shop_orders', ['show' => $open_order[0]->getId()]);
        }

        $addons = $this->shopService->getAddons();
        $userRegistered = $this->ticketService->userRegistered($user);
        $form = $this->createForm(CheckoutType::class, options: ['addons' => $addons, 'tickets' => !$userRegistered]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // add tickets to order
            $noTickets = intval($data['tickets'] ?? 0);
            $order = $this->shopService->createOrder($user);
            $this->shopService->orderAddTickets($order, $noTickets);

            // add addons to order
            foreach ($addons as $addon) {
                $cnt = $data['addon'.$addon->getId()] ?? 0;
                $this->shopService->orderAddAddon($order, $addon, $cnt);
            }

            // handle code
            $code = $data['code'] ?? '';
            if ($this->ticketService->ticketCodeUnused($code)) {
                if ($this->ticketService->redeemTicket($code, $user)) {
                    $this->addFlash('success', 'Ticket erfolgreich aktiviert.');
                } else {
                    $this->addFlash('error', 'Ticket konnte nicht aktiviert werden.');
                }
            }

            if (!$order->isEmpty()) {
                $this->shopService->saveOrder($order);
                $this->addFlash('success', "Order erfolgreich.");
                return $this->redirectToRoute('shop_orders');
            }

            return $this->redirect('/');
        }

        // show order dialog
        $order_count = count($orders);
        return $this->render('site/shop/checkout.html.twig', [
            'form' => $form->createView(),
            'addons' => $addons,
            'order_count' => $order_count,
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
        $show_id = $request->request->getInt('show', -1);

        /** @var User $user */
        $user = $this->getUser()->getUser();
        $orders = $this->shopOrderRepository->queryOrders($user->getUuid());

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
            $order = $order[0];
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
            $show_id = $order->getId();
        }

        // show open order with option to cancel
        return $this->render('site/shop/orders.html.twig', [
            'orders' => $orders,
            'show_id' => $show_id,
            'csrf_token_cancel' => self::CSRF_TOKEN_CANCEL,
        ]);
    }

/*
    #[Route(path: '', name: '')]
    public function index(Request $request): Response
    {
        if (!$this->settingService->isSet('lan.signup.enabled')) {
            return $this->redirectToRoute('index');
        }

        $user = $this->getUser()->getUser();

        if ($this->gamerService->gamerHasRegistered($user)) {
            $this->addFlash('warning', 'Du bist bereits zur Veranstaltung angemeldet!');

            return $this->redirectToRoute('index');
        }

        $fb = $this->createFormBuilder()
            ->add('confirm', CheckboxType::class, [
                'label' => 'Ich melde mich zur Veranstaltung an',
                'required' => true,
            ]);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('confirm')->getData()) {
                try {
                    $this->gamerService->gamerRegister($user);
                    $this->addFlash('success', 'Erfolgreich zur Veranstaltung angemeldet');
                    if ($this->settingService->isSet('site.title')) {
                        $message = "Du hast dich zu der Veranstaltung: \"{$this->settingService->get('site.title')}\" erfolgreich angemeldet!";
                    } else {
                        $message = 'Du hast dich zu der Veranstaltung erfolgreich angemeldet!';
                    }
                    $this->emailService->scheduleHook(
                        EmailService::APP_HOOK_LAN_SIGNUP,
                        EmailRecipient::fromUser($this->getUser()->getUser()), [
                            'message' => $message,
                        ]
                    );

                    return $this->redirectToRoute('index');
                } catch (GamerLifecycleException) {
                    $this->addFlash('error', 'Anmeldung ist fehlgeschlagen!');
                    $this->logger->error('Gamerregistrierung ist fehlgeschlagen.');
                }
            }
        }

        return $this->render('site/lan_signup/signup.html.twig', [
            'form' => $form->createView(),
        ]);
    }
*/
}