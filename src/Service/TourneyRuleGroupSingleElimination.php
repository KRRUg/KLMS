<?php

namespace App\Service;

use App\Entity\Tourney;

class TourneyRuleGroupSingleElimination extends TourneyRuleGroupStage
{
    protected function createKnockoutRule(Tourney $tourney, SettingService $settingService): TourneyRule
    {
        return new TourneyRuleSingleElimination($tourney, $settingService);
    }
}
