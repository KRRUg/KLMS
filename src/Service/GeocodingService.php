<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Map\Point;

class GeocodingService
{
    /**
     * @var array<string, Point|null>
     */
    private array $cache = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(default::GOOGLE_MAPS_API_KEY)%')] private readonly ?string $googleMapsApiKey = null,
    ) {
    }

    public function geocode(string $address): ?Point
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        if (array_key_exists($address, $this->cache)) {
            return $this->cache[$address];
        }

        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            return $this->cache[$address] = null;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'address' => $address,
                    'key' => $apiKey,
                ],
                'timeout' => 8,
            ]);

            $data = $response->toArray(false);
            if (($data['status'] ?? null) !== 'OK' || empty($data['results'][0]['geometry']['location'])) {
                return $this->cache[$address] = null;
            }

            $loc = $data['results'][0]['geometry']['location'];

            return $this->cache[$address] = new Point((float) ($loc['lat'] ?? 0), (float) ($loc['lng'] ?? 0));
        } catch (ExceptionInterface) {
            return $this->cache[$address] = null;
        }
    }

    public function getApiKey(): ?string
    {
        if (!empty($this->googleMapsApiKey)) {
            return $this->googleMapsApiKey;
        }

        $fallback = $_ENV['GOOGLE_MAPS_API_KEY'] ?? $_SERVER['GOOGLE_MAPS_API_KEY'] ?? getenv('GOOGLE_MAPS_API_KEY');

        return is_string($fallback) && $fallback !== '' ? $fallback : null;
    }

    public function hasApiKey(): bool
    {
        return $this->getApiKey() !== null;
    }

    public function calculateDistanceKm(Point $from, Point $to): float
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
