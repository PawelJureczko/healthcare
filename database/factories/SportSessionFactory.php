<?php

namespace Database\Factories;

use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(['type' => 'sport', 'sport_subtype' => 'squash']),
            'duration_s' => fake()->numberBetween(1800, 5400),
            'kcal' => fake()->optional()->numberBetween(200, 700),
            'avg_heart_rate' => fake()->optional()->numberBetween(110, 170),
            'intensity' => fake()->numberBetween(1, 5),
            'strava_activity_id' => null,
            'strava_raw' => null,
        ];
    }
}
