<?php

namespace App\Entity;

enum TourneyStage : int
{
    case Created = 0;
    case Registration = 1;
    case Seeding = 2;
    case Running = 3;
    case Finished = 4;

    public function getMessage(): string
    {
        return match ($this) {
            self::Created => 'Erstellt',
            self::Registration => 'Anmeldung',
            self::Seeding => 'Vorbereitung',
            self::Running => 'Spielen',
            self::Finished => 'Resultat',
        };
    }

    public function getAdjective(): string
    {
        return match ($this) {
            self::Created => 'ist erstellt',
            self::Registration => 'ist in Registrierung',
            self::Seeding => 'wird vorbereitet',
            self::Running => 'läuft',
            self::Finished => 'beendet',
        };
    }

    public function hasTree(bool $isAdmin = false): bool
    {
        return $this == self::Running || $this == self::Finished || ($isAdmin && $this == self::Seeding);
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
