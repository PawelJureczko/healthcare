<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Omega-3', 'Witamina D', 'Atorwastatyna']),
            'dose' => fake()->randomElement(['1000 mg', '2000 IU', '10 mg']),
            'started_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'stopped_at' => null,
        ];
    }
}
