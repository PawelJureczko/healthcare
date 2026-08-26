<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workout;

class ExerciseHistory
{
    /**
     * @param  array<int>  $exerciseIds
     * @return array<int, float|null>
     */
    public static function lastWeights(User $user, array $exerciseIds): array
    {
        $latestWorkoutDates = [];

        $workouts = Workout::forUser($user)
            ->where('type', 'gym')
            ->with(['gymExercises' => function ($query) use ($exerciseIds) {
                $query->whereIn('exercise_id', $exerciseIds)->with('gymSets');
            }])
            ->orderByDesc('date')
            ->get();

        $result = array_fill_keys($exerciseIds, null);

        foreach ($workouts as $workout) {
            foreach ($workout->gymExercises as $gymExercise) {
                $exerciseId = $gymExercise->exercise_id;

                if ($result[$exerciseId] !== null) {
                    continue;
                }

                $maxWeight = $gymExercise->gymSets
                    ->where('status', 'done')
                    ->max('weight_kg');

                if ($maxWeight !== null) {
                    $result[$exerciseId] = (float) $maxWeight;
                }
            }
        }

        return $result;
    }
}
