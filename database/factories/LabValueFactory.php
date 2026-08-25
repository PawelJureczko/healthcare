<?php

namespace Database\Factories;

use App\Models\LabMarker;
use App\Models\LabResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lab_result_id' => LabResult::factory(),
            'lab_marker_id' => LabMarker::factory(),
            'value' => fake()->randomFloat(2, 10, 250),
        ];
    }
}
