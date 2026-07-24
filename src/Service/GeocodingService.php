<?php

namespace App\Service;

use App\ValueObject\GeoPoint;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingService
{
    /**
    * @var array<string, GeoPoint|null>
     */
    private array $cache = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function geocode(string $address): ?GeoPoint
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        if (array_key_exists($address, $this->cache)) {
            return $this->cache[$address];
        }

        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ],
                'headers' => [
                    'User-Agent' => 'KLMS-for-DoT/1.0',
                    'Accept' => 'application/json',
                ],
                'timeout' => 8,
            ]);

            $data = $response->toArray(false);
            if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
                return $this->cache[$address] = null;
            }

            $loc = $data[0];

            return $this->cache[$address] = new GeoPoint((float) ($loc['lat'] ?? 0), (float) ($loc['lon'] ?? 0));
        } catch (ExceptionInterface) {
            return $this->cache[$address] = null;
        }
    }

    public function calculateDistanceKm(GeoPoint $from, GeoPoint $to): float
    {
        $earthRadiusKm = 6371;

        $latFrom = deg2rad($from->getLatitude());
        $latTo = deg2rad($to->getLatitude());
        $deltaLat = deg2rad($to->getLatitude() - $from->getLatitude());
        $deltaLng = deg2rad($to->getLongitude() - $from->getLongitude());

        $a = sin($deltaLat / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 1);
    }
}
