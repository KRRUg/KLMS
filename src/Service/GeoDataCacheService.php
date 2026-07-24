<?php

namespace App\Service;

use App\Entity\GeoData;
use App\Repository\GeoDataRepository;
use App\ValueObject\GeoPoint;

class GeoDataCacheService
{
    public function __construct(
        private readonly GeoDataRepository $geoDataRepository,
        private readonly GeocodingService $geocodingService,
    ) {
    }

    public function getPointForLocation(string $country, string $zip, string $city): ?GeoPoint
    {
        $normalized = $this->normalizeComponents($country, $zip, $city);
        if ($normalized === null) {
            return null;
        }

        $cached = $this->geoDataRepository->findOneByLocation(
            $normalized['country'],
            $normalized['zip'],
            $normalized['city']
        );

        if ($cached instanceof GeoData) {
            return new GeoPoint($cached->getLat(), $cached->getLon());
        }

        $point = $this->geocodingService->geocode($this->formatAddress($normalized));
        if (!$point) {
            return null;
        }

        $entity = (new GeoData())
            ->setCountry($normalized['country'])
            ->setZip($normalized['zip'])
            ->setCity($normalized['city'])
            ->setLat($point->getLatitude())
            ->setLon($point->getLongitude());

        $this->geoDataRepository->save($entity, true);

        return $point;
    }

    /**
     * @return array{country: string, zip: string, city: string}|null
     */
    private function normalizeComponents(string $country, string $zip, string $city): ?array
    {
        $country = trim($country);
        $zip = trim($zip);
        $city = trim($city);

        if ($country === '' || $zip === '' || $city === '') {
            return null;
        }

        return [
            'country' => $country,
            'zip' => $zip,
            'city' => $city,
        ];
    }

    /**
     * @param array{country: string, zip: string, city: string} $components
     */
    private function formatAddress(array $components): string
    {
        return sprintf('%s %s, %s', $components['zip'], $components['city'], $components['country']);
    }
}
