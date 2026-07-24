<?php

namespace App\Service;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\ValueObject\GeoPoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MapService
{
    private readonly IdmRepository $userRepository;

    public function __construct(
        IdmManager $manager,
        private readonly GeocodingService $geocodingService,
        private readonly SettingService $settings,
        private readonly GeoDataCacheService $geoDataCacheService,
        #[Autowire(service: 'cache.app')] private readonly CacheInterface $cache,
    ) {
        $this->userRepository = $manager->getRepository(User::class);
    }

    /**
     * @return array{center: array{lat: float, lng: float, title: string, zoom: int}, markers: array<int, array{lat: float, lng: float, title: string, address: string, count: int, distanceKm: float|null}>}
     */
    public function getUserMapPayload(): array
    {
        return $this->cache->get('site.user_map_payload.v1', function (ItemInterface $item): array {
            $item->expiresAfter(600);

            return $this->buildUserMapPayload();
        });
    }

    public function getCenterAddress(): string
    {
        return trim((string) $this->settings->get('map.center_coordinates', ''));
    }

    private function parseCoordinateString(string $input): ?GeoPoint
    {
        if ($input === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $input));
        if (count($parts) < 2) {
            return null;
        }

        $latitude = filter_var($parts[0], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($parts[1], FILTER_VALIDATE_FLOAT);

        if ($latitude === false || $longitude === false) {
            return null;
        }

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

          return new GeoPoint($latitude, $longitude);
    }

    /**
      * @return array<string, array{point: GeoPoint, address: string, count: int}>
     */
    private function collectUserLocations(): array
    {
        $buckets = [];

        foreach ($this->userRepository->findAll() as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $location = $this->extractUserLocation($user);
            if ($location === null) {
                continue;
            }

            $point = $this->geoDataCacheService->getPointForLocation(
                $location['country'],
                $location['zip'],
                $location['city']
            );
            if ($point === null) {
                continue;
            }

            $bucketKey = $this->buildPointKey($point);
            
            if (isset($buckets[$bucketKey])) {
                $buckets[$bucketKey]['count']++;
            } else {
                $buckets[$bucketKey] = [
                    'point' => $point,
                    'address' => $location['display'],
                    'count' => 1,
                ];
            }
        }

        return $buckets;
    }

    /**
    * @return array{country: string, zip: string, city: string, display: string}|null
     */
    private function extractUserLocation(User $user): ?array
    {
        $zip = $user->getPostcode();
        $city = $user->getCity();
        $country = $user->getCountry();

        $zip = $zip !== null ? trim((string) $zip) : '';
        $city = $city !== null ? trim((string) $city) : '';
        $country = $country !== null ? trim((string) $country) : '';

        if ($zip === '' || $city === '' || $country === '') {
            return null;
        }

        $display = implode(', ', array_filter([$zip, $city, $country]));

        return [
            'country' => $country,
            'zip' => $zip,
            'city' => $city,
            'display' => $display,
        ];
    }

    /**
     * @return array{center: array{lat: float, lng: float, title: string, zoom: int}, markers: array<int, array{lat: float, lng: float, title: string, address: string, count: int, distanceKm: float|null}>}
     */
    private function buildUserMapPayload(): array
    {
        $siteTitle = (string) $this->settings->get('site.title', 'LAN-Party');
        $configuredCenter = $this->parseCoordinateString($this->getCenterAddress());
        $centerPoint = $configuredCenter ?? new GeoPoint(48.2082, 16.3738);
        $centerZoom = $configuredCenter ? 7 : 5;

        $markers = [];
        foreach ($this->collectUserLocations() as $bucket) {
            $distanceKm = $configuredCenter
                ? $this->geocodingService->calculateDistanceKm($centerPoint, $bucket['point'])
                : null;

            $title = $distanceKm === null
                ? 'Community-Standort'
                : sprintf('Entfernung: %.1f km', $distanceKm);

            if ($bucket['count'] > 1) {
                $title .= sprintf(' · %d Nutzer', $bucket['count']);
            }

            $markers[] = [
                'lat' => $bucket['point']->getLatitude(),
                'lng' => $bucket['point']->getLongitude(),
                'title' => $title,
                'address' => $bucket['address'],
                'count' => $bucket['count'],
                'distanceKm' => $distanceKm,
            ];
        }

        usort($markers, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'center' => [
                'lat' => $centerPoint->getLatitude(),
                'lng' => $centerPoint->getLongitude(),
                'title' => $siteTitle,
                'zoom' => $centerZoom,
            ],
            'markers' => $markers,
        ];
    }

    private function buildPointKey(GeoPoint $point): string
    {
        return sprintf('%.6f:%.6f', $point->getLatitude(), $point->getLongitude());
    }
}
