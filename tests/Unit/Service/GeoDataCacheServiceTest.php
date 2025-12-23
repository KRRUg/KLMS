<?php

namespace App\Tests\Unit\Service;

use App\Entity\GeoData;
use App\Repository\GeoDataRepository;
use App\Service\GeoDataCacheService;
use App\Service\GeocodingService;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Point;

class GeoDataCacheServiceTest extends TestCase
{
    public function testReturnsPointFromCache(): void
    {
        $repository = $this->createMock(GeoDataRepository::class);
        $geocoding = $this->createMock(GeocodingService::class);
        $service = new GeoDataCacheService($repository, $geocoding);

        $geoData = (new GeoData())
            ->setCountry('Deutschland')
            ->setZip('1010')
            ->setCity('Wien')
            ->setLat(48.2082)
            ->setLon(16.3738);

        $repository->expects($this->once())
            ->method('findOneByLocation')
            ->with('Deutschland', '1010', 'Wien')
            ->willReturn($geoData);

        $geocoding->expects($this->never())->method('geocode');

    $point = $service->getPointForLocation(' Deutschland ', ' 1010 ', 'Wien ', null);

        $this->assertNotNull($point);
        $this->assertSame(48.2082, $point->getLatitude());
        $this->assertSame(16.3738, $point->getLongitude());
    }

    public function testGeocodesAndPersistsWhenMissing(): void
    {
        $repository = $this->createMock(GeoDataRepository::class);
        $geocoding = $this->createMock(GeocodingService::class);
        $service = new GeoDataCacheService($repository, $geocoding);

        $repository->expects($this->once())
            ->method('findOneByLocation')
            ->with('Deutschland', '10115', 'Berlin')
            ->willReturn(null);

        $point = new Point(52.5200, 13.4050);
        $geocoding->expects($this->once())
            ->method('geocode')
            ->with('10115 Berlin, Deutschland')
            ->willReturn($point);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (GeoData $entity) {
                return $entity->getCountry() === 'Deutschland'
                    && $entity->getZip() === '10115'
                    && $entity->getCity() === 'Berlin';
            }), true);

        $result = $service->getPointForLocation('Deutschland', '10115', 'Berlin', null);

        $this->assertSame($point, $result);
    }
}
