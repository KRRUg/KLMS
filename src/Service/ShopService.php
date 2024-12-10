<?php

namespace App\Service;

use App\Entity\ShopAddon;
use App\Entity\ShopOrder;
use App\Entity\ShopOrderHistory;
use App\Entity\ShopOrderHistoryAction;
use App\Entity\ShopOrderPositionAddon;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\ShopOrderStatus;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Helper\EmailRecipient;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\ShopAddonsRepository;
use App\Repository\ShopOrderPositionRepository;
use App\Repository\ShopOrderRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;


class ShopService
{
    private readonly ShopOrderRepository $orderRepository;
    private readonly ShopOrderPositionRepository $shopOrderPositionRepository;
    private readonly ShopAddonsRepository $shopAddonsRepository;
    private readonly SettingService $settingService;
    private readonly EntityManagerInterface $em;
    private readonly TicketService $ticketService;
    private readonly EmailService $emailService;
    private readonly IdmRepository $userRepo;

    public const DEFAULT_TICKET_PRICE = 5000;
    public const MAX_TICKET_COUNT = 20;
    public const MAX_ADDON_COUNT = 7;
    private LoggerInterface $logger;

    public function __construct(ShopOrderRepository $orderRepository, ShopOrderPositionRepository $shopOrderPositionRepository, ShopAddonsRepository $shopAddonsRepository,
                                IdmManager          $idmManager, SettingService $settingService, TicketService $ticketService, EmailService $emailService, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->orderRepository = $orderRepository;
        $this->shopOrderPositionRepository = $shopOrderPositionRepository;
        $this->shopAddonsRepository = $shopAddonsRepository;
        $this->userRepo = $idmManager->getRepository(User::class);
        $this->settingService = $settingService;
        $this->ticketService = $ticketService;
        $this->emailService = $emailService;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function getAll()
    {
        return $this->orderRepository->findAll();
    }

    private function checkLimits(ShopOrder $order): bool
    {
        $countTotal = $this->countOrderedAddons();
        $countUser = $this->countOrderedAddons($order->getOrderer());

        foreach ($order->getShopOrderPositions() as $pos) {
            if (!($pos instanceof ShopOrderPositionAddon)) {
                continue;
            }
            $addon = $pos->getAddon();
            $id = $addon->getId();
            if (!$addon->isActive()) {
                return false;
            }
            $countUser[$id] = ($countUser[$id] ?? 0) + 1;
            if ($addon->getOnlyOnce() && $countUser[$id] > 1) {
                return false;
            }
            $countTotal[$id] = ($countTotal[$id] ?? 0) + 1;
            if (!is_null($addon->getMaxQuantityGlobal())) {
                if ($countTotal[$id] > $addon->getMaxQuantityGlobal()) return false;
            }
        }

        return true;
    }

    private function fulfillOrder(ShopOrder $order): void
    {
        // handle tickets
        $buyer = $order->getOrderer();
        $first_ticket = null;
        foreach ($order->getShopOrderPositions() as $pos) {
            if ($pos instanceof ShopOrderPositionTicket) {
                $ticket = $this->ticketService->createTicket();
                $pos->setTicket($ticket);
                if (!$first_ticket) { $first_ticket = $ticket; }
            }
        }
        // activate one ticket for buyer if they don't have a ticket yet
        if ($first_ticket && !$this->ticketService->getTicketUser($buyer)) {
            $this->ticketService->redeemTicket($first_ticket, $buyer);
        }
    }

    private function emailOrder(ShopOrder $order): void
    {
        // notify the buyer and send the codes
        $user = $this->userRepo->findOneById($order->getOrderer());
        $this->emailService->scheduleHook(EmailService::APP_HOOK_ORDER, EmailRecipient::fromUser($user), [
            'order' => $order,
            'showPaymentInfo' => $order->getStatus() == ShopOrderStatus::Created,
            'showPaymentSuccess' => $order->getStatus() == ShopOrderStatus::Paid,
        ]);
    }

    public function placeOrder(ShopOrder $order): void
    {
        if ($order->isEmpty()) {
            throw new OrderLifecycleException($order);
        }
        if (!$this->checkLimits($order)) {
            throw new OrderLifecycleException($order);
        }
        $result = $this->setState($order, ShopOrderStatus::Created);
        if (!$result) {
            throw new OrderLifecycleException($order);
        }
    }

    public function cancelOrder(ShopOrder $order): void
    {
        $result = $this->setState($order, ShopOrderStatus::Canceled);
        if (!$result) {
            throw new OrderLifecycleException($order);
        }
    }

    public function setOrderPaid(ShopOrder $order): void
    {
        $result = $this->setState($order, ShopOrderStatus::Paid);
        if (!$result) {
            throw new OrderLifecycleException($order);
        }
    }

    public function setOrderPaidUndo(ShopOrder $order): void
    {
        $result = $this->setState($order, ShopOrderStatus::Created);
        if (!$result) {
            throw new OrderLifecycleException($order);
        }
    }

    private function setState(ShopOrder $order, ShopOrderStatus $status): bool
    {
        $new_state = match ($order->getStatus()) {
            // if the order has 0 amount, it is fulfilled immediately
            null => $order->calculateTotal() == 0 ? ShopOrderStatus::Paid : ShopOrderStatus::Created,
            // currently only state transfer from created to both other states are allowed.
            ShopOrderStatus::Created => $status,
            default => $order->getStatus()
        };
        if ($new_state != $order->getStatus()){
            $order->setStatus($new_state);
            $this->em->persist($order);
            $this->em->flush();
            $this->handleNewState($order);
            $this->em->flush();
            return true;
        } else {
            return false;
        }
    }

    private function handleNewState(ShopOrder $order): void
    {
        $this->logger->info("Order {$order->getId()} is now in stage {$order->getStatus()->name}");
        switch ($order->getStatus()) {
            case ShopOrderStatus::Created:
                $this->emailOrder($order);
                break;
            case ShopOrderStatus::Canceled:
                break;
            case ShopOrderStatus::Paid:
                $this->fulfillOrder($order);
                $this->emailOrder($order);
                break;
        }
        $order->addShopOrderHistory(
            (new ShopOrderHistory())
                ->setLoggedAt(new DateTimeImmutable())
                ->setAction(match ($order->getStatus()){
                    ShopOrderStatus::Created => ShopOrderHistoryAction::OrderCreated,
                    ShopOrderStatus::Paid => ShopOrderHistoryAction::PaymentSuccessful,
                    ShopOrderStatus::Canceled => ShopOrderHistoryAction::OrderCanceled,
                })
        );
    }

    public function orderAdheresToLimits(ShopOrder $order): bool
    {
        return $this->checkLimits($order);
    }

    public function hasOpenOrders(User|UuidInterface $user): bool
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->orderRepository->countOrders($uuid, ShopOrderStatus::Created) != 0;
    }

    public function getAddons(bool $all = false): array
    {
        return !$all ? $this->shopAddonsRepository->findActive() : $this->shopAddonsRepository->findAll();
    }

    public function allocOrder(User|UuidInterface $user): ShopOrder
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return (new ShopOrder())
            ->setOrderer($uuid)
            ->setCreatedAt(new DateTimeImmutable());
    }

    public function orderAddTickets(ShopOrder $order, int $ticketCnt): void
    {
        $price = $this->settingService->get('lan.signup.price', self::DEFAULT_TICKET_PRICE);
        $discount_limit = $this->settingService->get('lan.signup.discount.limit');
        $discount_price = $this->settingService->get('lan.signup.discount.price');
        if ($discount_price && $discount_limit && $ticketCnt >= $discount_limit) {
            $price = $discount_price;
        }
        for ($i = 0; $i < $ticketCnt; $i++) {
            $order->addShopOrderPosition((new ShopOrderPositionTicket())->setPrice($price));
        }
    }

    public function orderAddAddon(ShopOrder $order, ShopAddon $addon, int $cnt): void
    {
        for ($i = 0; $i < $cnt; $i++) {
            $order->addShopOrderPosition((new ShopOrderPositionAddon())->fillWithAddon($addon));
        }
    }

    /**
     * @param User|UuidInterface $user
     * @param ShopOrderStatus|null $status
     * @return ShopOrder[]
     */
    public function getOrderByUser(User|UuidInterface $user, ?ShopOrderStatus $status = null): array
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->orderRepository->queryOrders($uuid, $status);
    }

    public function deleteOrder(ShopOrder $order): void
    {
        if ($order->getStatus() !== ShopOrderStatus::Canceled) {
            throw new OrderLifecycleException($order);
        }
        $this->logger->info("Order {$order->getId()} was deleted.");
        $this->em->remove($order);
        $this->em->flush();
    }

    public function toggleAddonActivity(ShopAddon $addon): void
    {
        $addon->setActive(!$addon->isActive());
        $this->em->flush();
    }

    public function allocAddon(): ShopAddon
    {
        return (new ShopAddon())->setActive(false)->setPrice(100)->setName('Neues Addon')->setDescription('')->setOnlyOnce(false);
    }

    public function saveAddon(ShopAddon $addon): void
    {
        $this->em->persist($addon);
        $this->em->flush();
    }

    public function deleteAddon(ShopAddon $addon): void
    {
        $this->em->remove($addon);
        $this->em->flush();;
    }

    /**
     * @return array [[User, Text, Price],...]
     */
    public function getAddonOrders(): array
    {
        $sop = $this->shopOrderPositionRepository->getOrderedAddons(ShopOrderStatus::Paid);
        $uuids = array_map(fn($p) => $p->getOrder()->getOrderer(), $sop);

        // preload users
        $this->userRepo->findById($uuids);

        $result = [];
        foreach ($sop as $item) {
            $result[] = ['user' => $this->userRepo->findOneById($item->getOrder()->getOrderer()),
                'text' => $item->getText(), 'price' => $item->getPrice()];
        }
        return $result;
    }

    /**
     * @param User|UuidInterface|null $user An optimal user to count the purchases for that user.
     * @return array Array mapping AddonId to count of sold items of that addon.
     */
    public function countOrderedAddons(User|UuidInterface|null $user = null): array
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->shopOrderPositionRepository->countOrderedAddonsById($uuid);
    }

    /**
     * @param ShopAddon $addon The addon to be counted.
     * @return int The number of purchased items
     */
    public function countOrderedAddon(ShopAddon $addon): int
    {
        return $this->shopOrderPositionRepository->countOrderedAddons($addon);
    }

    /**
     * @param ShopAddon $addon The addon to be counted.
     * @param User|UuidInterface $user
     * @return int The number of purchased items
     */
    public function countOrderedAddonByUser(ShopAddon $addon, User|UuidInterface $user): int
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->shopOrderPositionRepository->countOrderedAddons($addon, $uuid);
    }
}
