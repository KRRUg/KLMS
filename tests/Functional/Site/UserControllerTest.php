<?php

namespace App\Tests\Functional\Site;

use App\Tests\Functional\DatabaseWebTestCase;

class UserControllerTest extends DatabaseWebTestCase
{
    public function testHomepageLoad()
    {
        $this->databaseTool->loadAllFixtures();
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testNewsLoad()
    {
        $this->databaseTool->loadAllFixtures();
        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
    }
}