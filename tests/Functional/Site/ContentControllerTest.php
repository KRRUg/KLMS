<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\ContentFixture;
use App\Tests\Functional\DatabaseWebTestCase;

class ContentControllerTest extends DatabaseWebTestCase
{
    public function testContentWithSlag()
    {
        $this->databaseTool->loadFixtures([ContentFixture::class]);

        $crawler = $this->client->request('GET', '/content/info');
        $this->assertEquals(0, $this->mock->getInvalidCalls());

        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals('FAQ', $crawler->filter('main h1')->eq(0)->text());
        $this->assertEquals('Wer ist dieser LAN?', $crawler->filter('main h1 + div')->eq(0)->text());
        $this->assertStringContainsString('zuletzt geändert', $crawler->filter('main')->text());
    }
}