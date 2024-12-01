<?php

namespace App\Tests\Integration\Service;

use App\Service\StatisticService;
use App\Tests\Integration\DatabaseTestCase;

class StatisticServiceIntegrationTest extends DatabaseTestCase
{
    public function testStatistic()
    {
        $this->databaseTool->loadAllFixtures();
        $stat = $this->getContainer()->get(StatisticService::class);
        $this->assertEmpty($stat->get('invalid_key'));
        $this->assertEquals('', $stat->get(''));
        $this->assertEquals(3, $stat->get('seats_free'));
        $this->assertEquals(9, $stat->get('seats_total'));
        $this->assertEquals(3, $stat->get('seats_taken'));
        $this->assertEquals(3, $stat->get('seats_locked'));
        $this->assertEquals(18, $stat->get('tickets_ordered'));
        $this->assertEquals(13, $stat->get('tickets_redeemed'));
    }
}