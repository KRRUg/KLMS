<?php

namespace App\Entity;

enum TourneyStatus : int
{
    case Created = 0;
    case Registration = 1;
    case Running = 2;
    case Finished = 3;

    public function getMessage(): string
    {
        return match ($this) {
            self::Created => '',
            self::Registration => 'Anmeldung offen',
            self::Running => 'läuft',
            self::Finished => 'beendet'
        };
    }
}
