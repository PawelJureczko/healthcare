<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BodyMeasurementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->unique()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'weight_kg' => fake()->randomFloat(2, 60, 120),
            'waist_cm' => fake()->optional()->randomFloat(2, 70, 120),
        ];
    }
}
