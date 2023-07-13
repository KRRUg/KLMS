<?php

namespace App\Entity;

enum TourneyRules : string
{
    case SingleElimination = 'se';
    case DoubleElimination = 'de';
    case RegistrationOnly = 'ro';

    public function getMessage(): string
    {
        return match ($this) {
            self::SingleElimination => 'Single Elimination',
            self::DoubleElimination => 'Double Elimination',
            self::RegistrationOnly => 'externes Turnier',
        };
    }

    public function hasTree(): bool
    {
        return $this != self::RegistrationOnly;
    }

    public function canHaveGames(): bool
    {
        return $this != self::RegistrationOnly;
    }

    public function canHaveTeams(): bool
    {
        return true;
    }
}