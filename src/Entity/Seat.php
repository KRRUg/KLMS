<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SeatRepository")
 * @ORM\Table(
 *     uniqueConstraints={@ORM\UniqueConstraint(name="seat_pos", columns={"pos_x", "pos_y"})}
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
     * @ORM\Column(type="string", length=10)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $locked = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\UserGamer", inversedBy="seats" )
     * @ORM\JoinColumn(name="owner", referencedColumnName="guid")
     */
    private $owner;

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

    public function getLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): self
    {
        $this->locked = $locked;

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
}
