<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\SeatmapFixture;
use App\Service\SettingService;
use App\Tests\Functional\DatabaseWebTestCase;

class SeatmapTest extends DatabaseWebTestCase
{
    public function testSeatmapLoad()
    {
        $this->databaseTool->loadFixtures([SeatmapFixture::class]);

        $crawler = $this->client->request('GET', '/seatmap');
        $this->assertResponseStatusCodeSame(200);
        $this->assertLessThanOrEqual(4, $this->mock->countRequests());
        $this->assertEquals(0, $this->mock->getInvalidCalls());

        $this->assertSelectorExists('.seatmap');
        $seatmap = $crawler->filter('#seatmap');
        $seats = $seatmap->filter('.seat');
        $this->assertCount(10, $seats);
        $this->assertCount(3, $seats->filter('.seat-empty'));
    }

    public function testSeatmapDisabled()
    {
        $this->databaseTool->loadFixtures([SeatmapFixture::class]);
        $service = $this->getContainer()->get(SettingService::class);
        $service->set('lan.seatmap.enabled', false);

        $this->client->followRedirects(false);
        $this->client->request('GET', '/seatmap' );
        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert');
    }

    public function testOwnSeatColor()
    {
        $this->databaseTool->loadFixtures([SeatmapFixture::class]);

        $this->login('user3@localhost.local');
        $crawler = $this->client->request('GET', '/seatmap' );
        $seatmap = $crawler->filter('#seatmap');
        $this->assertCount(1, $seatmap->filter('.seat.seat-own'));
        $this->assertCount(1, $seatmap->filter('.seat.seat-taken.seat-own-clan'));
        $this->assertCount(2, $seatmap->filter('.seat.seat-empty.seat-own-clan'));
    }

    public function testOwnClanColor()
    {
        $this->databaseTool->loadFixtures([SeatmapFixture::class]);

        $this->login('user2@localhost.local');
        $crawler = $this->client->request('GET', '/seatmap' );
        $seatmap = $crawler->filter('#seatmap');
        $this->assertCount(1, $seatmap->filter('.seat.seat-own'));
        $this->assertCount(4, $seatmap->filter('.seat.seat-own-clan'));
    }

    public function testTakeSeat()
    {
        $this->databaseTool->loadFixtures([SeatmapFixture::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/seatmap' );
        $seatmap = $crawler->filter('#seatmap');
        $this->assertCount(0, $seatmap->filter('.seat-own'));
        $seat = $crawler->filter('#seat5');
        $this->assertCount(1, $seat->filter('.seat-empty'));

        // get modal
        $crawler = $this->client->request('GET', '/seatmap/seat/5');
        $button = $crawler->filter('button[type=submit]');
        $this->assertStringContainsString('reservieren', $button->innerText());
        $crawler = $this->client->submit($button->form());
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('.alert');
        $this->assertCount(2, $crawler->filter('.seat-own'));
        $seat = $crawler->filter('#seat5');
        $this->assertCount(0, $seat->filter('.seat-empty'));
        $this->assertCount(1, $seat->filter('.seat-own'));
    }

    public function testFreeSeat()
    {
        $this->databaseTool->loadFixtures([SeatmapFixture::class]);

        $this->login('user3@localhost.local');
        $crawler = $this->client->request('GET', '/seatmap' );
        $seatmap = $crawler->filter('#seatmap');
        $this->assertCount(1, $seatmap->filter('.seat-own'));
        $this->assertCount(3, $seatmap->filter('.seat-own-clan'));
        $seat = $crawler->filter('#seat3');
        $this->assertCount(1, $seat->filter('.seat-own'));

        // get modal
        $crawler = $this->client->request('GET', '/seatmap/seat/3');
        $button = $crawler->filter('button[type=submit]');
        $this->assertStringContainsString('freigeben', $button->innerText());
        $crawler = $this->client->submit($button->form());
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('.alert.alert-success');
        $seatmap = $crawler->filter('#seatmap');
        $this->assertCount(0, $seatmap->filter('.seat-own'));
        $this->assertCount(3, $seatmap->filter('.seat-own-clan'));
        $seat = $crawler->filter('#seat3');
        $this->assertCount(1, $seat->filter('.seat-empty'));
        $this->assertCount(0, $seat->filter('.seat-own'));
    }

    public function testClanReservation()
    {
        $this->databaseTool->loadFixtures([SeatmapFixture::class]);
        $this->login('user14@localhost.local');

        // not for user14
        $crawler = $this->client->request('GET', '/seatmap' );
        $this->assertSelectorNotExists('.own-seat');
        $seat = $crawler->filter('#seat8');
        $this->assertEmpty($seat->filter('.seat-empty'));
        $this->assertNotEmpty($seat->filter('.seat-locked'));
        $crawler = $this->client->request('GET', '/seatmap/seat/8');
        $this->assertEmpty($crawler->filter('button[type=submit]'));
        $this->assertStringContainsString('Reserviert', $crawler->text());
        $this->assertStringContainsString('Clan 2', $crawler->text());

        $this->logout();
        $this->login('user4@localhost.local');

        // but for user4
        $crawler = $this->client->request('GET', '/seatmap' );
        $this->assertSelectorNotExists('.own-seat');
        $seat = $crawler->filter('#seat8');
        $this->assertNotEmpty($seat->filter('.seat-empty'));
        $this->assertNotEmpty($seat->filter('.seat-empty.seat-own-clan'));
        $this->assertEmpty($seat->filter('.seat-locked'));
        // reserve Seat
        $crawler = $this->client->request('GET', '/seatmap/seat/8');
        $button = $crawler->filter('button[type=submit]');
        $this->assertNotEmpty($button);
        $crawler = $this->client->submit($button->form());
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('.alert.alert-success');
    }
}