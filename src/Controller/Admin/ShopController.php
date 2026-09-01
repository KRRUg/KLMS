<?php

namespace App\Controller\Admin;

use App\Entity\ShopAddon;
use App\Entity\ShopOrder;
use App\Entity\ShopOrderPositionAddon;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Form\ShopAddonType;
use App\Form\UserSelectType;
use App\Repository\ShopOrderRepository;
use App\Service\SettingService;
use App\Service\ShopService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[IsGranted('ROLE_ADMIN_PAYMENT')]
#[Route(path: '/shop', name: 'shop')]
class ShopController extends AbstractController
{
    private const CSRF_TOKEN_PAID = 'shopToken';
    private const MAX_ORDER_LINES = 50;
    private const MAX_PRICE_CENTS = 100000;

    private readonly ShopService $shopService;
    private readonly ShopOrderRepository $orderRepository;
    private readonly SerializerInterface $serializer;
    private readonly UserService $userService;
    private readonly SettingService $settingService;

    public function __construct(ShopService $shopService, ShopOrderRepository $orderRepository, SerializerInterface $serializer, UserService $userService, SettingService $settingService)
    {
        $this->shopService = $shopService;
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
        $this->userService = $userService;
        $this->settingService = $settingService;
    }

    private function createOrderCaptureForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_shop_new'))
            ->setMethod('POST')
            ->add('user', UserSelectType::class, [
                'required' => true,
                'label' => 'User',
                'constraints' => [new Assert\NotNull()],
            ])
            ->getForm();
    }

    private static function toCents(mixed $value): int
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        $parsed = is_numeric($normalized) ? (float) $normalized : 0.0;
        return max(0, (int) round($parsed * 100));
    }

    /**
     * Fills the order from the submitted receipt lines and returns an error message, or null on success.
     */
    private function fillOrderFromLines(ShopOrder $order, array $lines, array $addonsById): ?string
    {
        if (count($lines) > self::MAX_ORDER_LINES) {
            return 'Zu viele Belegzeilen (maximal ' . self::MAX_ORDER_LINES . ').';
        }

        $ticketCount = 0;
        $addonCount = 0;

        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $itemKey = strtolower(trim((string) ($line['item'] ?? '')));
            $quantity = (int) ($line['quantity'] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            $priceCents = self::toCents($line['price'] ?? 0);
            if ($priceCents > self::MAX_PRICE_CENTS) {
                return 'Preis pro Stück darf maximal ' . number_format(self::MAX_PRICE_CENTS / 100, 2, ',', '.') . ' EUR betragen.';
            }

            if ($itemKey === 'ticket') {
                $ticketCount += $quantity;
                if ($ticketCount > ShopService::MAX_TICKET_COUNT) {
                    return 'Maximal ' . ShopService::MAX_TICKET_COUNT . ' Tickets pro Bestellung.';
                }
                for ($i = 0; $i < $quantity; $i++) {
                    $order->addShopOrderPosition((new ShopOrderPositionTicket())->setPrice($priceCents));
                }
                continue;
            }

            if (!str_starts_with($itemKey, 'addon:')) {
                continue;
            }

            $addon = $addonsById[(int) substr($itemKey, strlen('addon:'))] ?? null;
            if (!$addon) {
                continue;
            }

            $addonCount += $quantity;
            if ($addonCount > ShopService::MAX_ADDON_COUNT) {
                return 'Maximal ' . ShopService::MAX_ADDON_COUNT . ' Addons pro Bestellung.';
            }

            for ($i = 0; $i < $quantity; $i++) {
                $order->addShopOrderPosition(
                    (new ShopOrderPositionAddon())
                        ->fillWithAddon($addon)
                        ->setPrice($priceCents)
                );
            }
        }

        return null;
    }

    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        $orders = $this->orderRepository->findAll();

        $uuids = [];
        foreach ($orders as $order) {
            $orderer = $order->getOrderer();
            if ($orderer) {
                $uuids[$orderer->toString()] = $orderer;
            }
        }

        return $this->render('admin/shop/index.html.twig', [
            'orders' => $orders,
            'users' => $this->userService->getUsers(array_values($uuids), assoc: true),
        ]);
    }

    #[Route(path: '/order/new', name: '_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $addons = $this->shopService->getAddons();
        $form = $this->createOrderCaptureForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user = $form->isValid() ? ($form->getData()['user'] ?? null) : null;
            if (empty($user)) {
                $this->addFlash('error', 'Bestellung konnte nicht erstellt werden. Bitte einen gültigen User auswählen.');
            } else {
                $addonsById = [];
                foreach ($addons as $addon) {
                    $addonsById[$addon->getId()] = $addon;
                }

                try {
                    $lines = $request->request->all('lines');
                } catch (BadRequestException) {
                    $lines = [];
                }

                $order = $this->shopService->allocOrder($user);
                $error = $this->fillOrderFromLines($order, $lines, $addonsById);

                if ($error !== null) {
                    $this->addFlash('error', $error);
                } elseif ($order->isEmpty()) {
                    $this->addFlash('warning', 'Leere Bestellung kann nicht angelegt werden. Bitte mindestens eine Belegzeile mit Menge > 0 erfassen.');
                } elseif (!$this->shopService->orderAdheresToLimits($order, false)) {
                    $this->addFlash('error', 'Bestellung konnte nicht angelegt werden (Addon-Limits erreicht oder Addon nicht mehr verfügbar).');
                } else {
                    try {
                        $this->shopService->placeOrder($order);
                        $this->addFlash('success', "Bestellung #{$order->getId()} wurde für {$user->getNickname()} angelegt.");
                        return $this->redirectToRoute('admin_shop');
                    } catch (OrderLifecycleException) {
                        $this->addFlash('error', 'Bestellung konnte nicht angelegt werden.');
                    }
                }
            }
        }

        $addonMeta = array_map(static fn (ShopAddon $addon) => [
            'id' => $addon->getId(),
            'name' => $addon->getName(),
            'onlyOnce' => $addon->getOnlyOnce(),
            'priceCents' => $addon->getPrice(),
        ], $addons);

        return $this->render('admin/shop/new.html.twig', [
            'form' => $form->createView(),
            'addons' => $addonMeta,
            'defaultTicketPriceCents' => (int) $this->settingService->get('lan.signup.price', ShopService::DEFAULT_TICKET_PRICE),
            'maxPriceCents' => self::MAX_PRICE_CENTS,
        ]);
    }

    #[Route(path: '/order/{id}', name:'_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(Request $request, ShopOrder $order): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_PAID, $token)) {
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
                case 'refund':
                    $this->shopService->refundOrder($order);
                    break;
                default:
                    $this->addFlash('error', 'Ungültige Aktion.');
                    return $this->redirectToRoute('admin_shop');
            }
        } catch (OrderLifecycleException) {
            $this->addFlash('error', 'Aktion konnte nicht durchgeführt werden.');
            return $this->redirectToRoute('admin_shop');
        }

        $this->addFlash('success', "Änderung an Bestellung #{$order->getId()} erfolgreich.");
        return $this->redirectToRoute('admin_shop');
    }

    #[Route(path: '/order/{id}', name: '_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Request $request, ShopOrder $order): Response
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/shop/show.html.twig', [
            'order' => $order,
            'fulfillable' => $this->shopService->orderAdheresToLimits($order, true),
            'csrf_token' => self::CSRF_TOKEN_PAID
        ]);
    }

    #[Route(path: '/addon', name: '_addon', methods: ['GET'])]
    public function indexAddons(): Response
    {
        $addons = $this->shopService->getAddons(all: true);
        return $this->render('admin/shop/addon.html.twig', [
            'addons' => $addons,
            'countAddons' => $this->shopService->countOrderedAddons(),
            'countAddonsPaid' => $this->shopService->countOrderedAddons(null, true),
            'csrf_token' => self::CSRF_TOKEN_PAID,
        ]);
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
        return $this->render('admin/shop/show_addon.html.twig', [
            'form' => $form->createView(),
            'csrf_token' => self::CSRF_TOKEN_PAID
        ]);
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
            'csrf_token' => self::CSRF_TOKEN_PAID
        ]);
    }

    #[Route(path: '/addon/{id}/toggle', name:'_addon_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleAddon(Request $request, ShopAddon $addon): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_PAID, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token presented');
        }
        $this->shopService->toggleAddonActivity($addon);

        return $this->redirectToRoute('admin_shop_addon');
    }

    #[Route(path: '/addon/{id}/delete', name:'_addon_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteAddon(Request $request, ShopAddon $addon): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_PAID, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token presented');
        }
        $this->shopService->deleteAddon($addon);
        $this->addFlash('success', "Addon {$addon->getId()} wurde gelöscht.");
        return $this->redirectToRoute('admin_shop_addon');
    }

    #[Route(path: '/order/export', name:'_order_export', methods: ['GET'])]
    public function exportOrder(): Response
    {
        $csvData = [];

        $orders = $this->shopService->getOrders();

        foreach ($orders as $o) {
            /** @var User $user */
            $user = $o['user'];
            /** @var ShopOrder $o */
            $order = $o['order'];
            $csvData[] = [
                'uuid' => $user->getUuid()->toString(),
                'nickname' => $user->getNickname(),
                'vorname' => $user->getFirstname(),
                'nachname' => $user->getSurname(),
                'date' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'tickets' => $order->countTickets(),
                'amount' => $order->calculateTotal(),
                'status' => $order->getStatus()->name,
            ];
        }

        $response = new Response($this->serializer->serialize($csvData, 'csv'));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="orders.csv"');

        return $response;
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
