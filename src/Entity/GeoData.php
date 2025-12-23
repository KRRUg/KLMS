<?php

namespace App\Entity;

use App\Repository\GeoDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GeoDataRepository::class)]
#[ORM\Table(name: 'geodata')]
#[ORM\UniqueConstraint(name: 'uniq_geodata_location', columns: ['country', 'zip', 'city'])]
class GeoData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'country', type: 'string', length: 255)]
    private string $country;

    #[ORM\Column(name: 'zip', type: 'string', length: 10)]
    private string $zip;

    #[ORM\Column(name: 'city', type: 'string', length: 255)]
    private string $city;

    #[ORM\Column(name: 'lon', type: 'decimal', precision: 10, scale: 7, options: ['default' => 0.0])]
    private string $lon = '0.0';

    #[ORM\Column(name: 'lat', type: 'decimal', precision: 10, scale: 7, options: ['default' => 0.0])]
    private string $lat = '0.0';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setZip(string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getLon(): float
    {
        return (float) $this->lon;
    }

    public function setLon(float $lon): self
    {
        $this->lon = (string) $lon;

        return $this;
    }

    public function getLat(): float
    {
        return (float) $this->lat;
    }

    public function setLat(float $lat): self
    {
        $this->lat = (string) $lat;

        return $this;
    }
}
