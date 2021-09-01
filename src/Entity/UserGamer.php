<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserGamerRepository")
 */
class UserGamer
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    private ?UuidInterface $uuid;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $registered;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $payed;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Seat", mappedBy="owner")
     */
    private $seats;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $checkedIn;

    public function __construct(?UuidInterface $uuid)
    {
        $this->uuid = $uuid;
        $this->seats = new ArrayCollection();
    }

    public function getRegistered()
    {
        return $this->registered;
    }

    public function setRegistered($registered): self
    {
        $this->registered = $registered;

        return $this;
    }

    public function hasRegistered() : bool
    {
        return $this->registered !== null;
    }
    
    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getPayed(): ?DateTimeInterface
    {
        return $this->payed;
    }

    public function setPayed(?DateTimeInterface $payed): self
    {
        $this->payed = $payed;

        return $this;
    }

    public function hasPayed(): bool
    {
        return $this->payed !== null;
    }

    /**
     * @return Collection|Seat[]
     */
    public function getSeats(): Collection
    {
        return $this->seats;
    }

    public function addSeat(Seat $seat): self
    {
        if (!$this->seats->contains($seat)) {
            $this->seats[] = $seat;
            $seat->setOwner($this);
        }

        return $this;
    }

    public function removeSeat(Seat $seat): self
    {
        if ($this->seats->contains($seat)) {
            $this->seats->removeElement($seat);
            // set the owning side to null (unless already changed)
            if ($seat->getOwner() === $this) {
                $seat->setOwner(null);
            }
        }

        return $this;
    }

    public function getCheckedIn(): ?\DateTimeInterface
    {
        return $this->checkedIn;
    }

    public function setCheckedIn(?\DateTimeInterface $checkedIn): self
    {
        $this->checkedIn = $checkedIn;

        return $this;
    }
}
