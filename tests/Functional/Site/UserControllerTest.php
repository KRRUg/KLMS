<?php

namespace App\Tests\Functional\Site;

use App\Tests\Functional\DatabaseWebTestCase;

class UserControllerTest extends DatabaseWebTestCase
{
    public function testUserProfileShow()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
    }

}