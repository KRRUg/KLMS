<?php

namespace App\Entity;

enum TourneyType : string
{
    case single_elimination = 'se';
    case double_elimination = 'de';
    case registration_only = 'ro';
}