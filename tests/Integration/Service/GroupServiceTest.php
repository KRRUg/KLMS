<?php

namespace App\Tests\Integration\Service;

use App\Service\GroupService;
use App\Tests\Integration\DatabaseTestCase;

class GroupServiceTest extends DatabaseTestCase
{
    public function testGroupAdmin()
    {
        $this->databaseTool->loadAllFixtures();
        $groupService = $this->getContainer()->get(GroupService::class);
        $group = $groupService->query(GroupService::GROUP_ADMINS);
        $this->assertCount(2, $group);
    }

    public function testGroupPaid()
    {
        $this->databaseTool->loadAllFixtures();
        $groupService = $this->getContainer()->get(GroupService::class);
        $group = $groupService->query(GroupService::GROUP_PAID);
        $this->assertCount(13, $group);
    }

    public function testGroupPaidNoSeat()
    {
        $this->databaseTool->loadAllFixtures();
        $groupService = $this->getContainer()->get(GroupService::class);
        $group = $groupService->query(GroupService::GROUP_PAID_NO_SEAT);
        $this->assertCount(10, $group);
    }

    public function testGroupNewsletter()
    {
        $this->databaseTool->loadAllFixtures();
        $groupService = $this->getContainer()->get(GroupService::class);
        $group = $groupService->query(GroupService::GROUP_NEWSLETTER);
        $this->assertCount(4, $group);
    }
}