<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\SettingsFixture;
use App\DataFixtures\ShopFixture;
use App\DataFixtures\UserFixtures;
use App\Service\TicketService;
use App\Tests\Functional\DatabaseWebTestCase;
use Ramsey\Uuid\Uuid;

class ShopTest extends DatabaseWebTestCase
{
    public function testShopPage(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user8@localhost.local");

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);

        // count that there are 3 addons
        $addons = $crawler->filter('div[data-addon-id]');
        $this->assertCount(3, $addons);

        $addon0 = $addons->eq(0);
        $this->assertStringContainsString("Catering Guthaben", $addon0->filter('.card-header')->text());
        $this->assertStringContainsString("50,00", $addon0->filter('.card-body')->text());
        $input = $addon0->filter('input[type="number"]');
        $this->assertNotEmpty($input);
        $this->assertEquals('0', $input->attr('min'));

        $addon1 = $addons->eq(1);
        $this->assertStringContainsString("Catering Guthaben", $addon1->filter('.card-header')->text());
        $this->assertStringContainsString("100,00", $addon1->filter('.card-body')->text());
        $input = $addon1->filter('input[type="number"]');
        $this->assertNotEmpty($input);
        $this->assertEquals('0', $input->attr('min'));
        $this->assertEquals('2', $input->attr('max'));
        $this->assertStringContainsString("nur noch 2 Stück verfügbar", $addon1->text());

        $addon2 = $addons->eq(2);
        $this->assertStringContainsString("Chair", $addon2->filter('.card-header')->text());
        $this->assertStringContainsString("0,00", $addon2->filter('.card-body')->text());
        $input = $addon2->filter('input[type="checkbox"]');
        $this->assertNotEmpty($input);
    }

    public function testShopPageWithExistingOrders()
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user13@localhost.local");

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);

        // count that there are 3 addons
        $addons = $crawler->filter('div[data-addon-id]');
        $this->assertCount(3, $addons);

        // has already ordered own gaming chair
        $addon2 = $addons->eq(2);
        $this->assertStringContainsString("Chair", $addon2->filter('.card-header')->text());
        $this->assertStringContainsString("0,00", $addon2->filter('.card-body')->text());
        $input = $addon2->filter('input[type="checkbox"]');
        $this->assertNotEmpty($input);
        // check that $input is disabled
        $this->assertNotEmpty($input->attr('disabled'));
        $this->assertStringContainsString("Add-On nicht", $addon2->text());
    }

    public function testShopPageWithOpenOrders(): void
    {
        // user14 has an open order
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user14@localhost.local");

        // don't follow the redirect
        $this->client->followRedirects(false);
        $this->client->request('GET', '/shop/checkout');
        $this->assertResponseRedirects('/shop/orders?show=4');
        $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h2', 'Bestellübersicht');
    }

    public function testSubmitCheckout(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user8@localhost.local");
        $this->client->followRedirects(false);

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "1",
            'checkout[addon2]' => "0",
        ]);
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/shop/orders');
        $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('#order-list', 'Bestellnummer');
        $this->assertSelectorTextContains('#order-list', '50');
    }

    public function testSubmitEmptyForm(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user8@localhost.local");
        $this->client->followRedirects(false);

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "0",
            'checkout[addon2]' => "0",
        ]);
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('.alert', 'Leere Bestellung kann nicht angelegt werden.');
    }

    public function testOrderAllRemaining(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user8@localhost.local");

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "0",
            'checkout[addon2]' => "2", // only 2 left
        ]);
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        // the other use can't buy it anymore
        $this->logout();
        $this->login("user9@localhost.local");
        $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');
        $this->assertSelectorExists('div[data-addon-id=2] > * input[type="number"][disabled]');
        $this->assertSelectorTextContains('div[data-addon-id=2]','Dieses Addon ist nicht mehr verfügbar.');
    }

    public function testOrderAllRemainingCancel(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user8@localhost.local");

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "0",
            'checkout[addon2]' => "2", // only 2 left
        ]);
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        // cancel the order now
        $form = $crawler->selectButton('Bestellung stornieren')->form();
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('h2', 'Bestellübersicht');

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');
        $this->assertSelectorNotExists('div[data-addon-id=2] > * input[type="number"][disabled]');
        $this->assertSelectorTextNotContains('div[data-addon-id=2]','Dieses Addon ist nicht mehr verfügbar.');
    }

    public function testOrderSingleton(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user8@localhost.local");
        $this->client->followRedirects(false);

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "0",
            'checkout[addon2]' => "0",
            'checkout[addon4]' => "1", // that is the singleton with price 0
        ]);
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/shop/orders');
        $this->client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $this->client->followRedirects(false);
        $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200); // no redirect
        $this->assertSelectorTextContains('div[data-addon-id=4]', 'Du kannst dieses Add-On nicht (noch einmal) bestellen.');
        $this->assertSelectorExists('div[data-addon-id=4] > * input[type="checkbox"][disabled]');
    }

    public function testShopCancelOpenOrder()
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class, SettingsFixture::class]);
        $this->login("user14@localhost.local");

        $crawler = $this->client->request('GET', '/shop/orders');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h2', 'Bestellübersicht');
        $button = $crawler->selectButton("Bestellung stornieren");
        $this->assertNotEmpty($button);
        $this->client->submit($button->form());
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('.alert.alert-success');

        // check if a new order can be made
        $this->client->followRedirects(false);
        $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
    }

    private function provideCodes(): array
    {
        return [
            ["", false],
            ["INVALIC_FORMAT", false],
            ["CODE1-KRRUG-BBBBB", true],  // valid code
            ["CODE1-KRRUG-XXXXX", false], // invalid code
            ["CODE1-KRRUG-AAAAA", false], // valid but used code
        ];
    }

    /**
     * @dataProvider provideCodes
     */
    public function testCodeCheck(string $code, bool $expected): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class]);

        $this->login('user3@localhost.local');
        $this->client->request('GET', '/shop/check', empty($code) ? [] : ['code' => $code]);
        $this->assertResponseStatusCodeSame(200);
        $json = $this->client->getResponse()->getContent();
        $this->assertJson($json);
        $result = json_decode($json, associative: true, depth: 2);
        $this->assertArrayHasKey('result', $result);
        $this->assertIsBool($result['result']);
        $this->assertEquals($expected, $result['result']);
    }

    public function testCodeActivationWithoutShopping(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class]);
        $ticketService = $this->client->getContainer()->get(TicketService::class);
        $this->assertFalse($ticketService->isUserRegistered(Uuid::fromInteger(15)));

        $this->login('user15@localhost.local');

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "0",
            'checkout[addon2]' => "0",
            'checkout[code]' => "CODE1-KRRUG-BBBBB",
        ]);
        $this->client->followRedirects(false);
        $this->client->submit($form);

        // should redirect to home
        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Ticket erfolgreich aktiviert.');

        // check that user is on lan
        $this->assertTrue($ticketService->isUserRegistered(Uuid::fromInteger(15)));
    }

    public function testCodeActivationWithShopping(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class]);
        $ticketService = $this->client->getContainer()->get(TicketService::class);
        $this->assertFalse($ticketService->isUserRegistered(Uuid::fromInteger(15)));

        $this->login('user15@localhost.local');

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "0",
            'checkout[addon2]' => "0",
            'checkout[code]' => "CODE1-KRRUG-BBBBB",
        ]);
        $this->client->followRedirects(false);
        $this->client->submit($form);

        // should redirect to home
        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Ticket erfolgreich aktiviert.');

        // check that user is on lan
        $this->assertTrue($ticketService->isUserRegistered(Uuid::fromInteger(15)));
    }

    public function testCodeActivationInvalidCode(): void
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class]);
        $ticketService = $this->client->getContainer()->get(TicketService::class);
        $this->assertFalse($ticketService->isUserRegistered(Uuid::fromInteger(15)));

        $this->login('user15@localhost.local');

        $crawler = $this->client->request('GET', '/shop/checkout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1', 'LAN Eintritt kaufen');

        $button = $crawler->selectButton('Absenden');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->setValues([
            'checkout[tickets]' => "0",
            'checkout[addon1]' => "0",
            'checkout[addon2]' => "0",
            'checkout[code]' => "CODE1-KRRUG-AAAAA", // valid but used code
        ]);
        //$this->client->followRedirects(false);
        $this->client->submit($form);
        // check that user is on lan
        $this->assertFalse($ticketService->isUserRegistered(Uuid::fromInteger(15)));
    }
}