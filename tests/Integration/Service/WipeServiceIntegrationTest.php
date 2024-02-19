<?php

namespace App\Tests\Integration\Service;

use App\Repository\ContentRepository;
use App\Repository\MediaRepository;
use App\Repository\NavigationRepository;
use App\Repository\NewsRepository;
use App\Repository\SeatRepository;
use App\Repository\SponsorRepository;
use App\Repository\TeamsiteRepository;
use App\Repository\TourneyRepository;
use App\Repository\UserGamerRepository;
use App\Service\ContentService;
use App\Service\GamerService;
use App\Service\MediaService;
use App\Service\NavigationService;
use App\Service\NewsService;
use App\Service\SeatmapService;
use App\Service\SettingService;
use App\Service\SponsorService;
use App\Service\StatisticService;
use App\Service\TeamsiteService;
use App\Service\TourneyService;
use App\Service\WipeService;
use App\Tests\Integration\DatabaseTestCase;

class WipeServiceIntegrationTest extends DatabaseTestCase
{
    private function checkIfServiceIsEmpty(array $serviceIds, bool $checkForEmpty = true): void
    {
        $container = self::getContainer();
        foreach ($serviceIds as $id) {
            $service = $container->get($id);
            $repoId = match ($service::class) {
                ContentService::class => ContentRepository::class,
                GamerService::class => UserGamerRepository::class,
                MediaService::class => MediaRepository::class,
                NavigationService::class => NavigationRepository::class,
                NewsService::class => NewsRepository::class,
                SeatmapService::class => SeatRepository::class,
                SponsorService::class => SponsorRepository::class,
                TeamsiteService::class => TeamsiteRepository::class,
                TourneyService::class => TourneyRepository::class,
                SettingService::class => null,
                default => $this->fail()
            };
            if ($repoId) {
                $repo = $container->get($repoId);
                if ($checkForEmpty) {
                    $this->assertEmpty($repo->findAll(), "Failed to verify that class " . $id . " is empty.");
                } else {
                    $this->assertNotEmpty($repo->findAll(), "Failed to verify that class " . $id . " is not empty.");
                }
            }
        }
    }

    public function testNoCyclicDependencies()
    {
        $wipeService = self::getContainer()->get(WipeService::class);
        $all = $wipeService->getWipeableServiceIds();
        $sorted = $wipeService->buildOrder($all);
        $this->assertIsArray($all);
        $this->assertNotFalse($sorted);
        $this->assertIsArray($sorted);
        $this->assertCount(count($all), $sorted);
        $this->assertEquals(array_diff($all, $sorted), array_diff($sorted, $all));
    }

    public function testWipeAll()
    {
        $this->databaseTool->loadAllFixtures();
        $wipeService = self::getContainer()->get(WipeService::class);
        $wipeService->wipe();
        $this->checkIfServiceIsEmpty($wipeService->getWipeableServiceIds());
    }

    public function testWipeSingleService()
    {
        $this->databaseTool->loadAllFixtures();
        $wipeService = self::getContainer()->get(WipeService::class);
        $all = $wipeService->getWipeableServiceIds();
        $wipe = [TourneyService::class];
        $result = $wipeService->wipe($wipe);
        $this->assertTrue($result);
        $this->checkIfServiceIsEmpty($wipe, true);
        $this->checkIfServiceIsEmpty(array_diff($all, $wipe), false);
    }

    public function testInvalidClassWipe()
    {
        $this->databaseTool->loadAllFixtures();
        $wipeService = self::getContainer()->get(WipeService::class);
        $this->expectException(\LogicException::class);
        $wipeService->wipe([StatisticService::class]);
    }

    public function testInvalidDependencyWipe()
    {
        $this->databaseTool->loadAllFixtures();
        $wipeService = self::getContainer()->get(WipeService::class);
        $wipe = [GamerService::class];
        $result = $wipeService->wipe($wipe);
        $this->assertFalse($result);
    }
}
