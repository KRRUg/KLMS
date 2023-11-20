<?php

namespace App\Tests\Functional\Site;
;
use App\DataFixtures\SponsorFixtures;
use App\Tests\Functional\DatabaseWebTestCase;

class SponsorTest extends DatabaseWebTestCase
{
    public function testSponsors()
    {
        $this->databaseTool->loadFixtures([SponsorFixtures::class]);

        $crawler = $this->client->request('GET', '/sponsor');
        $this->assertEquals(0, $this->mock->getInvalidCalls());

        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $crawler->filter('.sponsor-category'));
        $sponsors = $crawler->filter('.sponsor');
        $this->assertCount(2, $sponsors);
    }
}