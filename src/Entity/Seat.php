<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeatRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *     @ORM\UniqueConstraint(name="seat_pos", columns={"pos_x", "pos_y"}),
 *     @ORM\UniqueConstraint(name="sector_seat", columns={"sector", "seat_number"})}
 * )
 */
class Seat
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="pos_x")
     */
    private $posX;

    /**
     * @ORM\Column(type="integer", name="pos_y")
     */
    private $posY;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $name;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserGamer", inversedBy="seats" )
     * @ORM\JoinColumn(name="owner", referencedColumnName="uuid")
     */
    private $owner;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $sector;

    /**
     * @ORM\Column(type="integer")
     */
    private $seatNumber;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $chairPosition;


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

    public function getOwner(): ?UserGamer
    {
        return $this->owner;
    }

    public function setOwner(?UserGamer $owner): self
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

    public function generateSeatName(): string
    {
        if ($this->getName())
            return $this->getName();
        else
            return "{$this->getSector()}-{$this->getSeatNumber()}";
    }
}
