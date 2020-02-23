<?php

namespace App\Entity;

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
    private $payed;

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
}
