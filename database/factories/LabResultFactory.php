<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'performed_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'note' => null,
        ];
    }
}
