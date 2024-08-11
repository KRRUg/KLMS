<?php

namespace App\Entity;

use App\Repository\SeatRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Table]
#[ORM\UniqueConstraint(name: 'seat_pos', columns: ['pos_x', 'pos_y'])]
#[ORM\UniqueConstraint(name: 'sector_seat', columns: ['sector', 'seat_number'])]
#[ORM\Entity(repositoryClass: SeatRepository::class)]
class Seat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'pos_x', type: 'integer')]
    private ?int $posX = null;

    #[ORM\Column(name: 'pos_y', type: 'integer')]
    private ?int $posY = null;

    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(name: 'owner', type: 'uuid', nullable: true)]
    private ?UuidInterface $owner = null;

    #[ORM\Column(type: 'string', length: 1)]
    private ?string $sector = null;

    #[ORM\Column(type: 'integer')]
    private ?int $seatNumber = null;

    #[ORM\Column(type: 'string', length: 32)]
    private ?string $type = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $chairPosition = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPosX(): ?int
    {
        return $this->posX;
    }

    public function setPosX(int $posX): self
    {
        $this->posX = $posX;

        return $this;
    }

    public function getPosY(): ?int
    {
        return $this->posY;
    }

    public function setPosY(int $posY): self
    {
        $this->posY = $posY;

        return $this;
    }

    public function getOwner(): ?UuidInterface
    {
        return $this->owner;
    }

    public function setOwner(?UuidInterface $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(string $sector): self
    {
        $this->sector = $sector;

        return $this;
    }

    public function getSeatNumber(): ?int
    {
        return $this->seatNumber;
    }

    public function setSeatNumber(int $seatNumber): self
    {
        $this->seatNumber = $seatNumber;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getChairPosition(): ?string
    {
        return $this->chairPosition;
    }

    public function setChairPosition(string $chairPosition): self
    {
        $this->chairPosition = $chairPosition;

        return $this;
    }

    public function getLocation(): string
    {
        return "{$this->getSector()}-{$this->getSeatNumber()}   ";
    }

    public function generateSeatName(): string
    {
        if ($this->getName()) {
            return $this->getName();
        } else {
            return "{$this->getSector()}-{$this->getSeatNumber()}";
        }
    }
}
