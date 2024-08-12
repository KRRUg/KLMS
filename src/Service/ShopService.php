<?php

namespace App\Service;

use App\Entity\ShopAddon;
use App\Entity\ShopOrder;
use App\Entity\ShopOrderPositionAddon;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\ShopOrderStatus;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Repository\ShopAddonsRepository;
use App\Repository\ShopOrderRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;


class ShopService
{
    private ShopOrderRepository $orderRepository;
    private ShopAddonsRepository $shopAddonsRepository;
    private SettingService $settingService;
    private EntityManagerInterface $em;
    private TicketService $ticketService;

    public const DEFAULT_TICKET_PRICE = 5000;

    public function __construct(ShopOrderRepository $orderRepository, ShopAddonsRepository $shopAddonsRepository,
                                SettingService      $settingService, TicketService $ticketService, EntityManagerInterface $em)
    {
        $this->orderRepository = $orderRepository;
        $this->shopAddonsRepository = $shopAddonsRepository;
        $this->settingService = $settingService;
        $this->ticketService = $ticketService;
        $this->em = $em;
    }

    public function getAll()
    {
        return $this->orderRepository->findAll();
    }

    private function fulfillOrder(ShopOrder $order): void
    {
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
        // currently only state transfer from created to both other states are allowed.
        $new_state = match ($order->getStatus()) {
            ShopOrderStatus::Created => $status,
            default => $order->getStatus()
        };
        if ($new_state != $order->getStatus()){
            $order->setStatus($new_state);
            $this->handleNewState($order);
            $this->saveOrder($order);
            return true;
        } else {
            return false;
        }
    }

    private function handleNewState(ShopOrder $order): void
    {
        switch ($order->getStatus()) {
            case ShopOrderStatus::Created:
            case ShopOrderStatus::Canceled:
                break;
            case ShopOrderStatus::Paid:
                $this->fulfillOrder($order);
                break;
        }
    }

    public function hasOpenOrders(User|UuidInterface $user): bool
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->orderRepository->countOrders($uuid, ShopOrderStatus::Created) != 0;
    }

    public function getAddons(): array
    {
        return $this->shopAddonsRepository->findActive();
    }

    public function createOrder(User|UuidInterface $user): ShopOrder
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return (new ShopOrder())
            ->setOrderer($uuid)
            ->setCreatedAt(new DateTimeImmutable())
            ->setStatus(ShopOrderStatus::Created);
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
            $order->addShopOrderPosition((new ShopOrderPositionAddon())->setAddon($addon));
        }
    }

    public function saveOrder(ShopOrder $order): void
    {
        if ($order->isEmpty()) {
            throw new OrderLifecycleException($order);
        }

        // TODO add order history entry
        $this->em->persist($order);
        $this->em->flush();
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
}