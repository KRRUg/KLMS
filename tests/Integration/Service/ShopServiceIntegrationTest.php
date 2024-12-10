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

        $order = $shopService->allocOrder($user);
        $this->expectException(OrderLifecycleException::class);
        // can't save empty order
        $shopService->placeOrder($order);
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

        $order = $shopService->allocOrder($user);
        $shopService->orderAddTickets($order, $count);
        $shopService->placeOrder($order);

        $this->assertEquals($expectedTotal, $order->calculateTotal());
        $this->assertCount($count, $order->getShopOrderPositions());
        $this->assertCount(1, $shopService->getOrderByUser($user));
        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
    }

    public function testAddonBuyDisabled(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, SettingsFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(8);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $addonsAll = $shopService->getAddons(true);
        $addons = $shopService->getAddons(false);
        $this->assertCount(4, $addonsAll);
        $this->assertCount(3, $addons);

        // get an addon that is disabled
        $addon = array_values(array_udiff($addonsAll, $addons, fn($a, $b) => $a->getId() - $b->getId()));
        $this->assertCount(1, $addon);
        $addon = $addon[0];
        $this->assertEquals("VIP Seat", $addon->getName());
        $this->assertFalse($addon->isActive());

        // try to buy it anyway
        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 1);

        $this->expectException(OrderLifecycleException::class);
        $shopService->placeOrder($order);
    }

    public function testAddonBuyOnlyOnce(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, SettingsFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(8);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $addons = array_values(array_filter($shopService->getAddons(), fn($a) => $a->getOnlyOnce() == true));
        $this->assertNotEmpty($addons);
        $addon = $addons[0];

        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 1);
        $this->assertEquals("Own Chair", $addon->getName());
        $shopService->placeOrder($order);
        $this->assertEquals(0, $order->calculateTotal());
        $this->assertEquals(ShopOrderStatus::Paid, $order->getStatus());
        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
    }

    public function testAddonBuyOnlyOnceTwice(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, SettingsFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(8);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $addons = array_values(array_filter($shopService->getAddons(), fn($a) => $a->getOnlyOnce() == true));
        $this->assertNotEmpty($addons);
        $addon = $addons[0];

        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 2);
        $this->assertEquals("Own Chair", $addon->getName());
        $this->expectException(OrderLifecycleException::class);
        $shopService->placeOrder($order);
    }

    public function testAddonBuyOnlyOnceAgain(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, SettingsFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(8);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $addons = array_values(array_filter($shopService->getAddons(), fn($a) => $a->getOnlyOnce() == true));
        $this->assertNotEmpty($addons);
        $addon = $addons[0];

        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 1);
        $shopService->placeOrder($order);
        $this->assertEquals(0, $order->calculateTotal());
        $this->assertEquals(ShopOrderStatus::Paid, $order->getStatus());

        $this->assertCount(1, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));

        // try to buy it again
        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 1);
        $this->expectException(OrderLifecycleException::class);
        $shopService->placeOrder($order);
    }

    public function testAddonBuyWithGlobalLimit(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, SettingsFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(8);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $addons = array_values(array_filter($shopService->getAddons(), fn($a) => !is_null($a->getMaxQuantityGlobal())));
        $this->assertNotEmpty($addons);
        $addon = $addons[0];
        $this->assertEquals(4, $addon->getMaxQuantityGlobal());
        $this->assertEquals(2, $shopService->countOrderedAddon($addon));

        // let's order one more
        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 1);
        $shopService->placeOrder($order);
        $this->assertEquals(3, $shopService->countOrderedAddon($addon));
        $this->assertEquals(ShopOrderStatus::Created, $order->getStatus());

        // let's pay the order
        $shopService->setOrderPaid($order);
        $this->assertEquals(ShopOrderStatus::Paid, $order->getStatus());
        $this->assertEquals(3, $shopService->countOrderedAddon($addon));

        // try to buy one more (reach the max)
        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 1);
        $shopService->placeOrder($order);
        $this->assertEquals(4, $shopService->countOrderedAddon($addon));
        $this->assertEquals(ShopOrderStatus::Created, $order->getStatus());

        $shopService->cancelOrder($order);
        $this->assertEquals(3, $shopService->countOrderedAddon($addon));
    }

    public function testAddonBuyWithExceedingGlobalLimit(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, SettingsFixture::class]);
        $shopService = $this->getContainer()->get(ShopService::class);
        $user = $this->getUser(8);

        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Created));
        $this->assertCount(0, $shopService->getOrderByUser($user, ShopOrderStatus::Paid));
        $addons = array_values(array_filter($shopService->getAddons(), fn($a) => !is_null($a->getMaxQuantityGlobal())));
        $this->assertNotEmpty($addons);
        $addon = $addons[0];
        $this->assertEquals(4, $addon->getMaxQuantityGlobal());
        $this->assertEquals(2, $shopService->countOrderedAddon($addon));

        // exceed the maximum
        $order = $shopService->allocOrder($user);
        $shopService->orderAddAddon($order, $addon, 3);
        $this->expectException(OrderLifecycleException::class);
        $shopService->placeOrder($order);
    }
}