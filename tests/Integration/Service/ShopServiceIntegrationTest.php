<?php

namespace App\Tests\Integration\Service;

use App\DataFixtures\SettingsFixture;
use App\DataFixtures\ShopFixture;
use App\Entity\ShopOrderPositionAddon;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\ShopOrderStatus;
use App\Entity\User;
use App\Exception\OrderLifecycleException;
use App\Idm\IdmManager;
use App\Service\SettingService;
use App\Service\ShopService;
use App\Service\TicketService;
use App\Tests\Integration\DatabaseTestCase;
use Ramsey\Uuid\Nonstandard\Uuid;

class ShopServiceIntegrationTest extends DatabaseTestCase
{
    private function getUser(int $id): ?User
    {
        $manager = self::getContainer()->get(IdmManager::class);
        $userRepo = $manager->getRepository(User::class);
        return $userRepo->findOneById(Uuid::fromInteger($id));
    }
    public function testOrderPaid()
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $ticketService = $this->getContainer()->get(TicketService::class);
        $user = $this->getUser(19);

        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Canceled));

        $order = $shopService->getOrderByUser($user, ShopOrderStatus::Created)[0];
        $this->assertEquals(ShopOrderStatus::Created, $order->getStatus());
        $this->assertEmpty($ticketService->getTicketUser($user));

        $shopService->setOrderPaid($order);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $this->assertEquals(ShopOrderStatus::Paid, $order->getStatus());
        $ticket = $ticketService->getTicketUser($user);
        $this->assertNotEmpty($ticket);
        $this->assertTrue($ticket->isRedeemed());
        $this->assertFalse($ticket->isPunched());
    }

    public function testOrderCancelled()
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $ticketService = $this->getContainer()->get(TicketService::class);
        $user = $this->getUser(19);

        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Canceled));

        $order = $shopService->getOrderByUser($user, ShopOrderStatus::Created)[0];
        $this->assertEquals(ShopOrderStatus::Created, $order->getStatus());
        $this->assertEmpty($ticketService->getTicketUser($user));

        $shopService->cancelOrder($order);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Canceled));
        $this->assertEquals(ShopOrderStatus::Canceled, $order->getStatus());
        $this->assertEmpty($ticketService->getTicketUser($user));
    }

    public function testCreateEmptyOrder()
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(3);

        $this->assertCount(0, $shopService->getOrderByUser($user));

        $order = $shopService->createOrder($user);
        $this->expectException(OrderLifecycleException::class);
        // can't save empty order
        $shopService->saveOrder($order);
    }

    private function setValue(string $key, ?int $value): void
    {
        $settingService = $this->getContainer()->get(SettingService::class);
        if (is_null($value)) {
            $settingService->remove($key);
        } else {
            $settingService->set($key, strval($value));
        }
    }

    private function getPriceData(): array
    {
        return [
            [1, 1234, 400, 9,     1234],
            [2, 1234, 400, 9, 2 * 1234],
            [3, 1234, 400, 3, 3 * 400],
            [9, 1234, 200, 3, 9 * 200],
            [1, 1234, 0, null, 1234],
            [5, 1234, 0, null, 5 * 1234],
            [5, 1234, null, 3, 5 * 1234],
            [1, null, 0, null,     ShopService::DEFAULT_TICKET_PRICE],
            [5, null, 0, null, 5 * ShopService::DEFAULT_TICKET_PRICE],
        ];
    }

    /**
     * @dataProvider getPriceData
     */
    public function testCreateOrderTicket(int $count, ?int $price, ?int $discountPrice, ?int $discountLimit, int $expectedTotal)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, SettingsFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(3);

        $this->setValue('lan.signup.price', $price);
        $this->setValue('lan.signup.discount.price', $discountPrice);
        $this->setValue('lan.signup.discount.limit', $discountLimit);

        $this->assertCount(0, $shopService->getOrderByUser($user));

        $order = $shopService->createOrder($user);
        $shopService->orderAddTickets($order, $count);
        $shopService->saveOrder($order);

        $this->assertEquals($expectedTotal, $order->calculateTotal());
        $this->assertCount($count, $order->getShopOrderPositions());
        $this->assertCount(1, $shopService->getOrderByUser($user));
        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
    }
}