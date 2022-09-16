<?php


namespace App\Service;


abstract class OptimalService
{
    private SettingService $settings;

    public function __construct(SettingService $settings)
    {
        $this->settings = $settings;
    }

    public function active() : bool
    {
        return $this->settings->get(static::getSettingKey(), false);
    }

    public function activate()
    {
        $this->settings->set(static::getSettingKey(), true);
    }

    protected static abstract function getSettingKey() : string;
}