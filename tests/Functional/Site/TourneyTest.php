<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\TourneyFixture;
use App\Tests\Functional\DatabaseWebTestCase;

class TourneyTest extends DatabaseWebTestCase
{
    public function testTourneyTree()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class]);

        $crawler = $this->client->request('GET', '/tourney/1');
    }
}