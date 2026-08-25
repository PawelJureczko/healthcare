<?php

namespace Database\Seeders;

use App\Models\LabMarker;
use Illuminate\Database\Seeder;

class LabMarkerSeeder extends Seeder
{
    /**
     * Reference ranges are general, commonly-cited defaults for context
     * only — not medical advice. Editable per spec by adding custom
     * markers alongside these; predefined ranges are not user-editable in M1.
     */
    public function run(): void
    {
        $markers = [
            ['name' => 'Cholesterol całkowity', 'unit' => 'mg/dl', 'norm_min' => null, 'norm_max' => 200],
            ['name' => 'LDL', 'unit' => 'mg/dl', 'norm_min' => null, 'norm_max' => 100],
            ['name' => 'HDL', 'unit' => 'mg/dl', 'norm_min' => 40, 'norm_max' => null],
            ['name' => 'Trójglicerydy', 'unit' => 'mg/dl', 'norm_min' => null, 'norm_max' => 150],
            ['name' => 'ALT', 'unit' => 'U/l', 'norm_min' => null, 'norm_max' => 41],
            ['name' => 'AST', 'unit' => 'U/l', 'norm_min' => null, 'norm_max' => 40],
            ['name' => 'GGTP', 'unit' => 'U/l', 'norm_min' => null, 'norm_max' => 60],
            ['name' => 'Glukoza', 'unit' => 'mg/dl', 'norm_min' => 70, 'norm_max' => 99],
        ];

        foreach ($markers as $marker) {
            LabMarker::firstOrCreate(
                ['name' => $marker['name']],
                [...$marker, 'is_predefined' => true]
            );
        }
    }
}
