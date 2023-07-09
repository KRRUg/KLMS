<?php

namespace App\Entity;

enum TourneyType : string
{
    case SingleElimination = 'se';
    case DoubleElimination = 'de';
    case RegistrationOnly = 'ro';

    public function hasTree(): bool
    {
        return $this != self::RegistrationOnly;
    }
}