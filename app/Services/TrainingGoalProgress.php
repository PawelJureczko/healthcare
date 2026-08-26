<?php

namespace App\Services;

use App\Models\TrainingGoal;

class TrainingGoalProgress
{
    public static function percent(TrainingGoal $goal): int
    {
        if ($goal->target_distance_m <= 0) {
            return 0;
        }

        $longestRunM = $goal->user->workouts()
            ->where('type', 'run')
            ->where('date', '>=', $goal->created_at->format('Y-m-d'))
            ->with('run')
            ->get()
            ->max(fn ($workout) => $workout->run?->distance_m ?? 0) ?? 0;

        return (int) min(100, round(($longestRunM / $goal->target_distance_m) * 100));
    }

    public static function refreshStatus(TrainingGoal $goal): void
    {
        if ($goal->status !== 'active') {
            return;
        }

        if (self::percent($goal) >= 100) {
            $goal->update(['status' => 'achieved']);
        }
    }
}
