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
            self::Created => 'Erstellt',
            self::Registration => 'Anmeldung',
            self::Running => 'Spielen',
            self::Finished => 'Resultat',
        };
    }

    public function getAdjective(): string
    {
        return match ($this) {
            self::Created => 'erstellt',
            self::Registration => 'in Registrierung',
            self::Running => 'läuft',
            self::Finished => 'beendet',
        };
    }

    public function hasTree(): bool
    {
        return $this == self::Running || $this == self::Finished;
    }

    public function canHaveTeams(): bool
    {
        return $this != self::Created;
    }

    public function canHaveGames(): bool
    {
        return $this != self::Registration && $this != self::Created;
    }
}
