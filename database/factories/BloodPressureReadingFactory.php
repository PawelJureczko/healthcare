<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodPressureReadingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'measured_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'systolic' => fake()->numberBetween(110, 135),
            'diastolic' => fake()->numberBetween(70, 88),
            'resting_pulse' => fake()->optional()->numberBetween(55, 80),
        ];
    }
}
