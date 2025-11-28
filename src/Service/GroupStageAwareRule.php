<?php

namespace App\Service;

interface GroupStageAwareRule
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getGroupTables(): array;

    public function hasKnockoutBracket(): bool;
}
