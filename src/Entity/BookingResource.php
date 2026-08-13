<?php

namespace App\Entity;

use App\Repository\BookingResourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingResourceRepository::class)]
#[ORM\Table(name: 'booking_resource')]
class BookingResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $priority = 0;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Number of identical, interchangeable units of this resource (e.g. 5 showers).
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $unitCount = 1;

    /**
     * Optional names for individual units, indexed by unit number - 1.
     * Example: ["Dusche 1 Halle 17", "Dusche 2 Halle 19"].
     *
     * @var array<int, string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $unitNames = null;

    /**
     * Length of a single bookable slot in minutes.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $slotDurationMinutes = 15;

    /**
     * Required pause between two bookings of the same unit in minutes.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $bufferMinutes = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $availableFrom;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $availableUntil;

    /**
     * Maximum number of active bookings a single user may hold for this resource, null = unlimited.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxBookingsPerUser = 1;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active = true;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(mappedBy: 'resource', targetEntity: Booking::class, orphanRemoval: true)]
    private Collection $bookings;

    public function __construct()
    {
        $this->availableFrom = new \DateTimeImmutable();
        $this->availableUntil = new \DateTimeImmutable();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUnitCount(): int
    {
        return $this->unitCount;
    }

    public function setUnitCount(int $unitCount): self
    {
        $this->unitCount = $unitCount;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getUnitNames(): array
    {
        if ($this->unitNames === null) {
            return [];
        }

        return array_values(array_filter(array_map(static fn(mixed $name) => trim((string) $name), $this->unitNames), static fn(string $name) => $name !== ''));
    }

    /**
     * @param array<int, string> $unitNames
     */
    public function setUnitNames(array $unitNames): self
    {
        $normalized = array_values(array_filter(array_map(static fn(string $name) => trim($name), $unitNames), static fn(string $name) => $name !== ''));
        $this->unitNames = $normalized !== [] ? $normalized : null;

        return $this;
    }

    public function getUnitLabel(int $unitNumber): string
    {
        $index = $unitNumber - 1;
        $names = $this->getUnitNames();

        if (isset($names[$index]) && $names[$index] !== '') {
            return $names[$index];
        }

        return sprintf('%s %d', $this->name, $unitNumber);
    }

    public function getUnitNamesText(): string
    {
        return implode("\n", $this->getUnitNames());
    }

    public function setUnitNamesText(?string $unitNamesText): self
    {
        $raw = $unitNamesText ?? '';
        $lines = preg_split('/\R+/', $raw) ?: [];
        $unitNames = array_values(array_filter(array_map(static fn(string $line) => trim($line), $lines), static fn(string $line) => $line !== ''));

        return $this->setUnitNames($unitNames);
    }

    public function getSlotDurationMinutes(): int
    {
        return $this->slotDurationMinutes;
    }

    public function setSlotDurationMinutes(int $slotDurationMinutes): self
    {
        $this->slotDurationMinutes = $slotDurationMinutes;

        return $this;
    }

    public function getBufferMinutes(): int
    {
        return $this->bufferMinutes;
    }

    public function setBufferMinutes(int $bufferMinutes): self
    {
        $this->bufferMinutes = max(0, $bufferMinutes);

        return $this;
    }

    public function getAvailableFrom(): \DateTimeImmutable
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(?\DateTimeImmutable $availableFrom): self
    {
        if ($availableFrom === null) {
            return $this;
        }

        $this->availableFrom = $availableFrom;

        return $this;
    }

    public function getAvailableUntil(): \DateTimeImmutable
    {
        return $this->availableUntil;
    }

    public function setAvailableUntil(?\DateTimeImmutable $availableUntil): self
    {
        if ($availableUntil === null) {
            return $this;
        }

        $this->availableUntil = $availableUntil;

        return $this;
    }

    public function getMaxBookingsPerUser(): ?int
    {
        return $this->maxBookingsPerUser;
    }

    public function setMaxBookingsPerUser(?int $maxBookingsPerUser): self
    {
        $this->maxBookingsPerUser = $maxBookingsPerUser;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }
}
