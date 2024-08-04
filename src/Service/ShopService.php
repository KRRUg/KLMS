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

    public function __construct(ShopOrderRepository $orderRepository, ShopAddonsRepository $shopAddonsRepository,
                                SettingService $settingService, EntityManagerInterface $em)
    {
        $this->orderRepository = $orderRepository;
        $this->shopAddonsRepository = $shopAddonsRepository;
        $this->settingService = $settingService;
        $this->em = $em;
    }

    public function getAll()
    {
        return $this->orderRepository->findAll();
    }

    public function fulfillOrder(ShopOrder $order)
    {

    }

    public function cancelOrder(ShopOrder $order): void
    {
        $result = $this->setState($order, ShopOrderStatus::Canceled);
        if (!$result) {
            throw new OrderLifecycleException($order);
        }
    }

    public function setOrderPaid(ShopOrder $order, $delay=false): void
    {
        $result = $this->setState($order, ShopOrderStatus::Paid, $delay);
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

    private function setState(ShopOrder $order, ShopOrderStatus $status, bool $delay = false): bool
    {
        // TODO check if processing is allowed and process.
        switch ($order->getStatus()) {
            case ShopOrderStatus::Created:
            case ShopOrderStatus::Canceled:
            case ShopOrderStatus::Paid:
            default:
        }
        return true;
    }

    public function hasOpenOrders(User|UuidInterface $user): bool
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->orderRepository->countOrders($uuid, [ShopOrderStatus::Created]) != 0;
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
        $price = $this->settingService->get('lan.signup.price');
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
        // TODO add order history entry
        $this->em->persist($order);
        $this->em->flush();
    }
}