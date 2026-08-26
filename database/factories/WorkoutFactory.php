<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'run',
            'sport_subtype' => null,
            'date' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'status' => 'completed',
            'comment' => null,
            'wellbeing_rating' => null,
        ];
    }
}
