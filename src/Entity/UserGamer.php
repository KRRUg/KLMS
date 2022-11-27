<?php

namespace App\Entity;

use App\Repository\UserGamerRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: UserGamerRepository::class)]
class UserGamer
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?UuidInterface $uuid;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $registered = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $paid = null;

    #[ORM\OneToMany(targetEntity: Seat::class, mappedBy: 'owner')]
    private Collection $seats;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $checkedIn = null;

    public function __construct(?UuidInterface $uuid)
    {
        $this->uuid = $uuid;
        $this->seats = new ArrayCollection();
    }

    public function getRegistered(): ?DateTimeInterface
    {
        return $this->registered;
    }

    public function setRegistered($registered): self
    {
        $this->registered = $registered;

        return $this;
    }

    public function hasRegistered(): bool
    {
        return $this->registered !== null;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getPaid(): ?DateTimeInterface
    {
        return $this->paid;
    }

    public function setPaid(?DateTimeInterface $paid): self
    {
        $this->paid = $paid;

        return $this;
    }

    public function hasPaid(): bool
    {
        return $this->paid !== null;
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

    public function getCheckedIn(): ?DateTimeInterface
    {
        return $this->checkedIn;
    }

    public function setCheckedIn(?DateTimeInterface $checkedIn): self
    {
        $this->checkedIn = $checkedIn;

        return $this;
    }

    public function hasCheckedIn(): bool
    {
        return $this->checkedIn !== null;
    }
}
