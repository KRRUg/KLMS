<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\NewsFixtures;
use App\Tests\Functional\DatabaseWebTestCase;

class NewsControllerTest extends DatabaseWebTestCase
{
    public function testNewsLoad()
    {
        $this->databaseTool->loadFixtures([NewsFixtures::class]);

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals(1, $this->mock->countRequests());
        $this->assertEquals(0, $this->mock->getInvalidCalls());

        $div_news = $crawler->filter('#news');
        $this->assertEquals(6, $div_news->children()->count());

        $crawler = $this->client->request('GET', '/?cnt=8');
        $this->assertResponseStatusCodeSame(200);
        $div_news = $crawler->filter('#news');
        $this->assertEquals(8, $div_news->children()->count());
    }

    public function testNewsContent()
    {
        $this->databaseTool->loadFixtures([NewsFixtures::class]);

        $crawler = $this->client->request('GET', '/?cnt=1');
        $this->assertEquals(1, $this->mock->countRequests());
        $this->assertEquals(0, $this->mock->getInvalidCalls());

        $this->assertResponseStatusCodeSame(200);
        $div_news = $crawler->filter('#news')->eq(0);
        $this->assertEquals('lorem ipsum', $div_news->filter('h2 a')->text());
        $this->assertStringStartsWith('/news/', $div_news->filter('h2 a')->attr('href'));
        $this->assertStringContainsString('gepostet von User 2', $div_news->text());
        $this->assertStringContainsString('mehr', $div_news->text());
        $this->assertNotEmpty($div_news->filter('p'));
        $this->assertGreaterThan(10, strlen($div_news->filter('p')->eq(0)->text()));
    }
}