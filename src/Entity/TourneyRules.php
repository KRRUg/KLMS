<?php

namespace App\Entity;

enum TourneyRules : string
{
    case SingleElimination = 'se';
    case DoubleElimination = 'de';
    case RegistrationOnly = 'ro';
    case GroupSingleElimination = 'gs';
    case GroupDoubleElimination = 'gd';

    public function getMessage(): string
    {
        return match ($this) {
            self::SingleElimination => 'Single Elimination',
            self::DoubleElimination => 'Double Elimination',
            self::RegistrationOnly => 'externes Turnier',
            self::GroupSingleElimination => 'Gruppenphase + Single Elimination',
            self::GroupDoubleElimination => 'Gruppenphase + Double Elimination',
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

    public function requiresGroupStage(): bool
    {
        return match ($this) {
            self::GroupSingleElimination,
            self::GroupDoubleElimination => true,
            default => false,
        };
    }
}