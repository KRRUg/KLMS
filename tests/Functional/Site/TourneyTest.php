<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\TourneyFixture;
use App\DataFixtures\UserFixtures;
use App\Tests\Functional\DatabaseWebTestCase;

class TourneyTest extends DatabaseWebTestCase
{
    public function testTourneyListNoLogin()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user14@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertEquals(2, $crawler->filter('.tourney')->count());
        $this->assertEquals(1, $crawler->filter('.tourney.registered')->count());
        $this->logout();
        $this->login('user2@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertEquals(2, $crawler->filter('.tourney.registered')->count());
    }

    public function testTourneyTree()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $crawler = $this->client->request('GET', '/tourney/1');
    }
}