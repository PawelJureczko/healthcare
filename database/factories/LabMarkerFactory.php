<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LabMarkerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'unit' => 'mg/dl',
            'norm_min' => null,
            'norm_max' => fake()->randomFloat(2, 100, 200),
            'is_predefined' => false,
        ];
    }
}
