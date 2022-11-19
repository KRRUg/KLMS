<?php

namespace App\Service;

abstract class OptimalService
{
    private readonly SettingService $settings;

    public function __construct(SettingService $settings)
    {
        $this->settings = $settings;
    }

    public function active(): bool
    {
        return $this->settings->get(static::getSettingKey(), false);
    }

    public function activate()
    {
        $this->settings->set(static::getSettingKey(), true);
    }

    abstract protected static function getSettingKey(): string;
}
