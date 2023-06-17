<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\NavigationFixture;
use App\DataFixtures\SettingsFixture;
use App\Tests\Functional\DatabaseWebTestCase;

class SiteTest extends DatabaseWebTestCase
{
    public function testHomepageLoad()
    {
        $this->databaseTool->loadFixtures([]);
        $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testHomepageTitle()
    {
        $this->databaseTool->loadFixtures([SettingsFixture::class]);
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $meta_description = $crawler->filter('title')->eq(0)->text();
        $this->assertEquals('KRRU Lan Management System - News', $meta_description);
    }

    public function testNavigation()
    {
        $this->databaseTool->loadFixtures([NavigationFixture::class]);
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $nav = $crawler->filter('nav');
        $this->assertEquals(1, $nav->count());
        $nav_items = $nav->eq(0)->filter('ul > li > a');
        $this->assertEquals(11, $nav_items->count());
        $this->assertEquals('Home', $nav_items->eq(0)->text());
        $this->assertEquals('Lan Party', $nav_items->eq(1)->text());
        $this->assertEquals('Facts', $nav_items->eq(2)->text());
        $this->assertEquals('FAQ', $nav_items->eq(5)->text());
        $this->assertEquals('Team', $nav_items->eq(7)->text());

        $bottom_nav = $crawler->filter('#bottom-nav');
        $this->assertEquals(1, $bottom_nav->count());
        $nav_items = $bottom_nav->eq(0)->filter('a');
        $this->assertEquals(3, $nav_items->count());
        $this->assertEquals('AGB', $nav_items->eq(0)->text());
        $this->assertEquals('Impressum', $nav_items->eq(1)->text());
        $this->assertEquals('Datenschutz', $nav_items->eq(2)->text());
    }

    public function testSocialMediaLinks() {
        $this->databaseTool->loadFixtures([SettingsFixture::class]);
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $button_group = $crawler->filter('.social-btn-group');
        $this->assertEquals(1, $button_group->count());
        $buttons = $button_group->filter('a');
        $this->assertEquals(2, $buttons->count());
        $this->assertNotEmpty($button_group->filter('a[title=Steam]'));
        $this->assertNotEmpty($button_group->filter('a[title=Discord]'));
        $this->assertEmpty($button_group->filter('a[title=Facebook]'));
    }
}