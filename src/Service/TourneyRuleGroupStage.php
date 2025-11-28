<?php

namespace App\Service;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use App\Exception\ServiceException;

abstract class TourneyRuleGroupStage extends TourneyRule implements GroupStageAwareRule
{
    private TourneyRule $knockoutRule;

    public function __construct(Tourney $tourney, SettingService $settingService)
    {
        parent::__construct($tourney, $settingService);
        $this->knockoutRule = $this->createKnockoutRule($tourney, $settingService);
    }

    abstract protected function createKnockoutRule(Tourney $tourney, SettingService $settingService): TourneyRule;

    public function seed(array $list): void
    {
        $this->validateConfiguration(count($list));
        foreach ($this->tourney->getTeams() as $team) {
            $team->setGroupKey(null);
        }
        $groups = $this->distributeTeams($list);
        $this->createGroupMatches($groups);
    }

    public function processGame(TourneyGame $game, bool $overwrite): void
    {
        if ($game->isGroupStage()) {
            if ($this->areAllGroupGamesCompleted()) {
                $this->seedKnockoutIfRequired();
            }
            return;
        }

        $this->knockoutRule->processGame($game, $overwrite);
    }

    public function podium(): array
    {
        if (!$this->hasKnockoutBracket()) {
            return [];
        }

        return $this->knockoutRule->podium();
    }

    public function getFinal(): ?TourneyGame
    {
        return $this->hasKnockoutBracket() ? $this->knockoutRule->getFinal() : null;
    }

    public function getTrees(): array
    {
        if (!$this->hasKnockoutBracket()) {
            return [];
        }

        return $this->knockoutRule->getTrees();
    }

    public function isCompleted(): bool
    {
        return $this->hasKnockoutBracket() && $this->knockoutRule->isCompleted();
    }

    public function getGroupTables(): array
    {
        $tables = [];
        foreach ($this->tourney->getTeams() as $team) {
            $groupKey = $team->getGroupKey();
            if (!$groupKey) {
                continue;
            }
            if (!isset($tables[$groupKey])) {
                $tables[$groupKey] = [];
            }
            $tables[$groupKey][$team->getId() ?? spl_object_hash($team)] = $this->createEmptyRow($team);
        }

        foreach ($this->tourney->getGames() as $game) {
            if (!$game->isGroupStage() || !$game->isSeeded() || !$game->isDone()) {
                continue;
            }
            $groupKey = $game->getGroupKey();
            $teamA = $game->getTeamA();
            $teamB = $game->getTeamB();
            if (!$groupKey || !$teamA || !$teamB) {
                continue;
            }
            if (!isset($tables[$groupKey])) {
                continue;
            }

            $keyA = $teamA->getId() ?? spl_object_hash($teamA);
            $keyB = $teamB->getId() ?? spl_object_hash($teamB);
            if (!isset($tables[$groupKey][$keyA]) || !isset($tables[$groupKey][$keyB])) {
                continue;
            }

            $this->applyResult($tables[$groupKey][$keyA], $tables[$groupKey][$keyB], $game->getScoreA(), $game->getScoreB());
        }

        ksort($tables);
        foreach ($tables as &$standings) {
            usort($standings, fn (array $a, array $b) => $this->compareStandings($a, $b));
        }

        return $tables;
    }

    public function hasKnockoutBracket(): bool
    {
        return !empty($this->getKnockoutGames());
    }

    protected function collectAdvancingTeams(): array
    {
        $advance = max(0, (int) $this->tourney->getGroupAdvance());
        if ($advance === 0) {
            return [];
        }

        $qualified = [];
        foreach ($this->getGroupTables() as $standings) {
            $slice = array_slice($standings, 0, $advance);
            foreach ($slice as $entry) {
                $qualified[] = $entry['team'];
            }
        }

        return $qualified;
    }

    private function validateConfiguration(int $teamCount): void
    {
        $groupCount = $this->tourney->getGroupCount();
        $advance = $this->tourney->getGroupAdvance();
        if (empty($groupCount) || empty($advance)) {
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'Gruppenkonfiguration ist unvollständig.');
        }
        if ($groupCount < 1 || $advance < 1) {
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'Gruppenkonfiguration muss positive Zahlen verwenden.');
        }
        if ($groupCount > $teamCount) {
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'Mehr Gruppen als Teams sind nicht erlaubt.');
        }
        $maxPerGroup = (int) ceil($teamCount / $groupCount);
        if ($advance > $maxPerGroup) {
            throw new ServiceException(ServiceException::CAUSE_INVALID, 'Mehr Qualifikanten als Teams pro Gruppe sind nicht möglich.');
        }
    }

    /**
     * @param array<string, array<TourneyTeam>> $groups
     */
    private function createGroupMatches(array $groups): void
    {
        foreach ($groups as $groupKey => $teams) {
            $count = count($teams);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $game = (new TourneyGame())
                        ->setGroupStage(true)
                        ->setGroupKey($groupKey)
                        ->setTeamA($teams[$i])
                        ->setTeamB($teams[$j]);
                    $this->tourney->addGame($game);
                }
            }
        }
    }

    /**
     * @return array<string, array<TourneyTeam>>
     */
    private function distributeTeams(array $teams): array
    {
        $groupCount = (int) $this->tourney->getGroupCount();
        $groups = [];
        for ($i = 0; $i < $groupCount; $i++) {
            $groups[$this->groupLabel($i)] = [];
        }
        $groupKeys = array_keys($groups);
        foreach ($teams as $index => $team) {
            if (!$team instanceof TourneyTeam) {
                continue;
            }
            $slot = $groupKeys[$index % $groupCount];
            $team->setGroupKey($slot);
            $groups[$slot][] = $team;
        }

        return $groups;
    }

    private function groupLabel(int $position): string
    {
        $label = '';
        $positionCopy = $position;
        do {
            $label = chr(ord('A') + ($positionCopy % 26)) . $label;
            $positionCopy = intdiv($positionCopy, 26) - 1;
        } while ($positionCopy >= 0);

        return $label;
    }

    private function areAllGroupGamesCompleted(): bool
    {
        foreach ($this->tourney->getGames() as $game) {
            if ($game->isGroupStage() && !$game->isDone()) {
                return false;
            }
        }
        return true;
    }

    private function seedKnockoutIfRequired(): void
    {
        if ($this->hasKnockoutBracket()) {
            return;
        }
        $qualified = $this->collectAdvancingTeams();
        if (count($qualified) < 2) {
            return;
        }
        $this->knockoutRule->seed($qualified);
    }

    /**
     * @return TourneyGame[]
     */
    private function getKnockoutGames(): array
    {
        return array_values(array_filter(
            $this->tourney->getGames()->toArray(),
            fn (TourneyGame $game) => !$game->isGroupStage()
        ));
    }

    private function createEmptyRow(TourneyTeam $team): array
    {
        return [
            'team' => $team,
            'points' => 0,
            'scored' => 0,
            'conceded' => 0,
        ];
    }

    private function applyResult(array &$rowA, array &$rowB, ?int $scoreA, ?int $scoreB): void
    {
        if ($scoreA === null || $scoreB === null) {
            return;
        }

        // Update scores
        $rowA['scored'] += $scoreA;
        $rowA['conceded'] += $scoreB;
        $rowB['scored'] += $scoreB;
        $rowB['conceded'] += $scoreA;

        // Update points
        if ($scoreA > $scoreB) {
            $rowA['points'] += 3;
        } elseif ($scoreB > $scoreA) {
            $rowB['points'] += 3;
        } else {
            $rowA['points']++;
            $rowB['points']++;
        }
    }

    private function compareStandings(array $a, array $b): int
    {
        // 1. Punkte
        $pointsDiff = $b['points'] <=> $a['points'];
        if ($pointsDiff !== 0) {
            return $pointsDiff;
        }

        // 2. Head-to-Head (direkter Vergleich)
        $h2h = $this->getHeadToHeadResult($a['team'], $b['team']);
        if ($h2h !== 0) {
            return $h2h;
        }

        // 3. Score-Differenz
        $diffA = $a['scored'] - $a['conceded'];
        $diffB = $b['scored'] - $b['conceded'];
        $scoreDiff = $diffB <=> $diffA;
        if ($scoreDiff !== 0) {
            return $scoreDiff;
        }

        // 4. Erzielte Scores
        $scoredDiff = $b['scored'] <=> $a['scored'];
        if ($scoredDiff !== 0) {
            return $scoredDiff;
        }

        // 5. Alphabetisch (Fallback)
        return strcmp($a['team']->getName() ?? '', $b['team']->getName() ?? '');
    }

    /**
     * Get head-to-head result between two teams
     * @return int -1 if team A won, 1 if team B won, 0 if draw or no game played
     */
    private function getHeadToHeadResult(TourneyTeam $teamA, TourneyTeam $teamB): int
    {
        $groupKey = $teamA->getGroupKey();
        if (!$groupKey || $groupKey !== $teamB->getGroupKey()) {
            return 0;
        }

        foreach ($this->tourney->getGames() as $game) {
            if (!$game->isGroupStage() || !$game->isDone() || $game->getGroupKey() !== $groupKey) {
                continue;
            }

            $gameTeamA = $game->getTeamA();
            $gameTeamB = $game->getTeamB();
            
            if (!$gameTeamA || !$gameTeamB) {
                continue;
            }

            // Check if this is the game between our two teams
            if (($gameTeamA->getId() === $teamA->getId() && $gameTeamB->getId() === $teamB->getId())) {
                $scoreA = $game->getScoreA();
                $scoreB = $game->getScoreB();
                if ($scoreA === null || $scoreB === null) {
                    return 0;
                }
                return $scoreA > $scoreB ? -1 : ($scoreB > $scoreA ? 1 : 0);
            }
            
            if (($gameTeamA->getId() === $teamB->getId() && $gameTeamB->getId() === $teamA->getId())) {
                $scoreA = $game->getScoreA();
                $scoreB = $game->getScoreB();
                if ($scoreA === null || $scoreB === null) {
                    return 0;
                }
                return $scoreB > $scoreA ? -1 : ($scoreA > $scoreB ? 1 : 0);
            }
        }

        return 0;
    }
}
