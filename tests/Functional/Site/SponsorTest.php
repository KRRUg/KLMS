<?php

namespace App\Tests\Functional\Site;
use App\DataFixtures\SponsorFixtures;
use App\Service\SettingService;
use App\Service\SponsorService;
use App\Tests\Functional\DatabaseWebTestCase;

class SponsorTest extends DatabaseWebTestCase
{
    public function testSponsor()
    {
        $this->databaseTool->loadFixtures([SponsorFixtures::class]);

        $crawler = $this->client->request('GET', '/sponsor');
        $this->assertEquals(0, $this->mock->getInvalidCalls());

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $crawler->filter('.sponsor-category'));
        $sponsors = $crawler->filter('.sponsor');
        $this->assertCount(2, $sponsors);

        // Sponsor links are not enabled by default
        $this->assertSelectorNotExists('main a.btn');

        // only one has a link set
        $this->assertCount(2, $crawler->filter('.sponsor img'));
        $this->assertCount(1, $crawler->filter('.sponsor a > img'));
        $a = $crawler->filter('.sponsor a');
        $this->assertCount(1, $a);
        $this->assertEquals('https://www.example.com', $a->attr('href'));
    }

    public function testSponsorLinks()
    {
        $this->databaseTool->loadFixtures([SponsorFixtures::class]);
        $settings = static::getContainer()->get(SettingService::class);
        $settings->set('sponsor.page.site_links', true);

        $crawler = $this->client->request('GET', '/sponsor');
        $this->assertEquals(0, $this->mock->getInvalidCalls());

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('main a.btn');
        $this->assertSelectorTextSame('main a.btn', 'Hyper-Sponsor');
    }

    public function testSponsorText()
    {
        $this->databaseTool->loadFixtures([SponsorFixtures::class]);
        $settings = static::getContainer()->get(SettingService::class);

        $title = "FuuBaa";
        $text = "This is our Text";
        $settings->set('sponsor.page.title', $title);
        $settings->set('sponsor.page.text', $text);

        $this->client->request('GET', '/sponsor');
        $this->assertResponseStatusCodeSame(200);

        $this->assertSelectorTextSame('.container h1', $title);
        $this->assertSelectorTextSame('.container h1 + p', $text);
    }

    public function testSponsorEmptyCategories()
    {
        $this->databaseTool->loadFixtures([SponsorFixtures::class]);
        $settings = static::getContainer()->get(SettingService::class);
        $settings->set('sponsor.page.show_empty', true);

        $crawler = $this->client->request('GET', '/sponsor');
        $this->assertResponseStatusCodeSame(200);

        $this->assertCount(3, $crawler->filter('.sponsor-category'));
        $this->assertCount(2, $crawler->filter('.sponsor'));
        $this->assertSelectorExists('.sponsor-category h3');
    }

    public function testSponsorNoCategory()
    {
        $this->databaseTool->loadFixtures([SponsorFixtures::class]);
        $settings = static::getContainer()->get(SettingService::class);
        $settings->set('sponsor.page.show_header', false);
        $settings->set('sponsor.page.site_links', false);

        $crawler = $this->client->request('GET', '/sponsor');
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $crawler->filter('.sponsor-category'));
        $this->assertSelectorNotExists('.sponsor-category h3');
    }

    public function testSponsorEnable()
    {
        $this->databaseTool->loadFixtures([]);

        $this->client->request('GET', '/sponsor');
        $this->assertResponseStatusCodeSame(404);

        $sponsor_service = static::getContainer()->get(SponsorService::class);
        $settings = static::getContainer()->get(SettingService::class);

        $this->assertFalse($sponsor_service->active());
        $sponsor_service->activate();
        $this->assertTrue($sponsor_service->active());

        $this->client->request('GET', '/sponsor');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorNotExists('.sponsor-catebory');
        $this->assertSelectorNotExists('.sponsor');

        $settings->set('sponsor.page.show_empty', true);
        $this->client->request('GET', '/sponsor');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('.sponsor-category');
        $this->assertSelectorNotExists('.sponsor');
    }
}