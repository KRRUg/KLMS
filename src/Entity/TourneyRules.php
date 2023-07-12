<?php

namespace App\Entity;

enum TourneyRules : string
{
    case SingleElimination = 'se';
    case DoubleElimination = 'de';
    case RegistrationOnly = 'ro';

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