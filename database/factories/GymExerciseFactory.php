<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

class GymExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(['type' => 'gym']),
            'exercise_id' => Exercise::factory(),
            'order' => 0,
        ];
    }
}
