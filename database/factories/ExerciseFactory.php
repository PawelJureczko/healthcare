<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'muscle_group' => fake()->randomElement(['nogi', 'plecy', 'klatka', 'barki', 'ramiona', 'core']),
            'lumbar_risk' => false,
            'is_predefined' => false,
        ];
    }
}
