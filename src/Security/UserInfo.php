<?php

namespace App\Security;

/**
 * User information to be cached in KLMS. This class contains everything that is required in user to user interaction
 * (e.g. seatmap)
 * @package App\Security
 */
class UserInfo
{
    protected $uuid;

    protected $nickname;

    protected $clans = [];

    public function getNickname() : string
    {
        return $this->nickname;
    }

    public function setNickname($nickname): self
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getClans(): array
    {
        return $this->clans;
    }

    public function setClans(array $clans): void
    {
        $this->clans = $clans;
    }
}