<?php

namespace App\Service;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\GeoDataCacheService;
use Symfony\UX\Map\Icon\Icon;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

class MapService
{
    private readonly IdmRepository $userRepository;

    public function __construct(
        IdmManager $manager,
        private readonly GeocodingService $geocodingService,
        private readonly SettingService $settings,
        private readonly GeoDataCacheService $geoDataCacheService,
    ) {
        $this->userRepository = $manager->getRepository(User::class);
    }

    public function buildUserMap(): Map
    {
        $centerAddress = $this->getCenterAddress();
        $centerPoint = $this->parseCoordinateString($centerAddress);

        $map = (new Map())
            ->zoom(7)
            ->fitBoundsToMarkers(true);

        $locationBuckets = $this->collectUserLocations();

        foreach ($locationBuckets as $bucket) {
            $map->addMarker($this->createUserMarker($bucket, $centerPoint));
        }

        $this->configureMapCenter($map, $centerPoint, $centerAddress);

        return $map;
    }

    public function getCenterAddress(): string
    {
        return trim((string) $this->settings->get('map.center_coordinates', ''));
    }

    private function parseCoordinateString(string $input): ?Point
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

        return new Point($latitude, $longitude);
    }

    /**
     * @return array<string, array{point: Point, address: string, count: int}>
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
     * @param array{point: Point, address: string, count: int} $bucket
     */
    private function createUserMarker(array $bucket, ?Point $centerPoint): Marker
    {
        $title = $this->formatDistanceTitle($centerPoint, $bucket['point']);
        if ($bucket['count'] > 1) {
            $title .= sprintf(' · %d Nutzer', $bucket['count']);
        }

        $infoContent = sprintf(
            '<div style="color: black;"><strong>%s</strong><br>%s</div>',
            htmlspecialchars($title, ENT_QUOTES),
            htmlspecialchars($bucket['address'], ENT_QUOTES)
        );

        return new Marker(
            position: $bucket['point'],
            title: $title,
            infoWindow: new InfoWindow(content: $infoContent, opened: false, autoClose: true),
            extra: [
                'userCount' => $bucket['count'],
                'address' => $bucket['address'],
            ],
            icon: $this->buildUserDotIcon(),
        );
    }

    private function configureMapCenter(Map $map, ?Point $centerPoint, string $centerAddress): void
    {
        if ($centerPoint) {
            $map->center($centerPoint);
            $map->addMarker($this->createCenterMarker($centerPoint));
        } else {
            $defaultCenter = new Point(48.2082, 16.3738); // Vienna
            $map->center($defaultCenter);
            $map->zoom(5);
            $map->addMarker($this->createCenterMarker($defaultCenter));
        }
    }

    private function createCenterMarker(Point $point): Marker
    {
        $siteTitle = $this->settings->get('site.title', 'LAN-Party');
        $content = sprintf('<div style="color: black;"><strong>%s</strong></div>', htmlspecialchars($siteTitle, ENT_QUOTES));

        return new Marker(
            position: $point,
            title: $siteTitle,
            infoWindow: new InfoWindow(content: $content, opened: false),
            extra: ['type' => 'center'],
            id: 'map-center',
            icon: $this->buildCenterIcon(),
        );
    }

    private function formatDistanceTitle(?Point $centerPoint, Point $markerPoint): string
    {
        if (!$centerPoint) {
            return 'Zentrum nicht gesetzt';
        }

        $distanceKm = $this->geocodingService->calculateDistanceKm($centerPoint, $markerPoint);

        return sprintf('Entfernung: %.1f km', $distanceKm);
    }

    private function buildPointKey(Point $point): string
    {
        return sprintf('%.6f:%.6f', $point->getLatitude(), $point->getLongitude());
    }

    private function buildCenterIcon(): Icon
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
  <circle cx="20" cy="20" r="16" fill="#df1238" fill-opacity="0.12" />
  <circle cx="20" cy="20" r="9" fill="#df1238" />
  <circle cx="20" cy="20" r="3" fill="#ffffff" />
  <path d="M20 6v6M20 28v6M6 20h6M28 20h6" stroke="#df1238" stroke-width="2" stroke-linecap="round" />
</svg>
SVG;

        return Icon::svg($svg);
    }

    private function buildUserDotIcon(): Icon
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
    <circle cx="8" cy="8" r="4" fill="#df1238"/>
</svg>
SVG;

        return Icon::svg($svg);
    }
}
