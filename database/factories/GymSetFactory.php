<?php

namespace Database\Factories;

use App\Models\GymExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

class GymSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_exercise_id' => GymExercise::factory(),
            'set_number' => 1,
            'planned_weight_kg' => fake()->randomFloat(2, 20, 100),
            'planned_reps' => fake()->numberBetween(5, 12),
            'weight_kg' => null,
            'reps' => null,
            'status' => 'pending',
        ];
    }
}
