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
use App\Service\SponsorService;
use App\Service\TeamsiteService;
use App\Service\TourneyService;
use App\Service\WipeService;
use App\Tests\Integration\DatabaseTestCase;

class WipeServiceIntegrationTest extends DatabaseTestCase
{
    private function checkWipedService(array $serviceIds): void
    {
        $container = self::getContainer();
        foreach ($serviceIds as $id) {
            $service = $container->get($id);
            $repo = $container->get(
                match ($service::class) {
                    ContentService::class => ContentRepository::class,
                    GamerService::class => UserGamerRepository::class,
                    MediaService::class => MediaRepository::class,
                    NavigationService::class => NavigationRepository::class,
                    NewsService::class => NewsRepository::class,
                    SeatmapService::class => SeatRepository::class,
                    SponsorService::class => SponsorRepository::class,
                    TeamsiteService::class => TeamsiteRepository::class,
                    TourneyService::class => TourneyRepository::class,
                    default => $this->fail()
                }
            );
            $this->assertCount(0, $repo->findAll());
        }
    }

    public function testNoCyclicDependencies()
    {
        $wipeService = self::getContainer()->get(WipeService::class);
        $all = $wipeService->getWipeableServiceIds();
        $sorted = $wipeService->sortDependencies($all);
        $this->assertIsArray($all);
        $this->assertNotFalse($sorted);
        $this->assertIsArray($sorted);
        $this->assertCount(count($all), $sorted);
        $this->assertEquals(array_diff($all, $sorted), array_diff($sorted, $all));
    }

    public function testWipeAll()
    {
        $this->databaseTool->loadFixtures();

        $wipeService = self::getContainer()->get(WipeService::class);
        $wipeService->wipe();
        $this->checkWipedService($wipeService->getWipeableServiceIds());
    }

    public function testWipeSingleService()
    {

    }
}
