<?php

namespace App\Service;

use App\Entity\Tourney;

class TourneyRuleGroupDoubleElimination extends TourneyRuleGroupStage
{
    protected function createKnockoutRule(Tourney $tourney, SettingService $settingService): TourneyRule
    {
        return new TourneyRuleDoubleElimination($tourney, $settingService);
    }
}
