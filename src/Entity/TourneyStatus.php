<?php

namespace App\Entity;

enum TourneyStatus : int
{
    case created = 0;
    case registration = 1;
    case running = 2;
    case finished = 3;

    public function getMessage(): string
    {
        return match ($this) {
            self::created => '',
            self::registration => 'Anmeldung offen',
            self::running => 'läuft',
            self::finished => 'beendet'
        };
    }
}
