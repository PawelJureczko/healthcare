<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'age' => fake()->numberBetween(25, 55),
            'height_cm' => fake()->numberBetween(155, 200),
            'weight_goal_kg' => fake()->randomFloat(2, 55, 90),
            'injuries' => null,
            'dietary_preferences' => null,
        ];
    }
}
