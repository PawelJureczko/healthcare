<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingGoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'run_distance',
            'target_distance_m' => 7500,
            'target_date' => now()->addMonths(2)->format('Y-m-d'),
            'target_time_s' => null,
            'status' => 'active',
        ];
    }
}
