<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserGamerRepository")
 */
class UserGamer
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="guid", unique=true)
     */
    private $guid;

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

    public function __construct(string $guid)
    {
        $this->guid = $guid;
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
    
    public function getId(): ?string
    {
        return $this->guid;
    }

    public function setId(string $guid)
    {
        $this->guid = $guid;
    }

    public function getPayed(): ?\DateTimeInterface
    {
        return $this->payed;
    }

    public function setPayed(?\DateTimeInterface $payed): self
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
}
