<?php

namespace Database\Factories;

use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

class RunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(['type' => 'run']),
            'distance_m' => fake()->numberBetween(2000, 15000),
            'duration_s' => fake()->numberBetween(600, 5400),
            'avg_pace_s_per_km' => null,
            'avg_heart_rate' => fake()->optional()->numberBetween(120, 180),
            'max_heart_rate' => null,
            'kcal' => fake()->optional()->numberBetween(200, 900),
            'strava_activity_id' => null,
            'strava_raw' => null,
        ];
    }
}
