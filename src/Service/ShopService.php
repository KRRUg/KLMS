<?php

namespace App\Service;

use App\Entity\ShopOrder;
use App\Entity\ShopOrderStatus;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Repository\ShopOrderRepository;
use Ramsey\Uuid\UuidInterface;


class ShopService
{
    private ShopOrderRepository $orderRepository;

    public function __construct(ShopOrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
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
}