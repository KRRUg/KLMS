<?php

namespace App\Tests\Functional\Site;

use App\Tests\Functional\DatabaseWebTestCase;

class NewsControllerTest extends DatabaseWebTestCase
{
    public function testNewsLoad()
    {
        $this->databaseTool->loadAllFixtures();
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $div_news = $crawler->filter('#news');
        $this->assertEquals(6, $div_news->children()->count());
    }
}