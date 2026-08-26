<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * Predefined starter dictionary covering major muscle groups. Two
     * exercises are flagged lumbar_risk (classic barbell deadlift and
     * bent-over barbell row) per spec §4.3's "ochrona odcinka lędźwiowego"
     * rule, alongside safer variants of the same movement pattern.
     */
    public function run(): void
    {
        $exercises = [
            ['name' => 'Przysiad ze sztangą', 'muscle_group' => 'nogi', 'lumbar_risk' => false],
            ['name' => 'Wyciskanie sztangi leżąc', 'muscle_group' => 'klatka', 'lumbar_risk' => false],
            ['name' => 'Wyciskanie żołnierskie', 'muscle_group' => 'barki', 'lumbar_risk' => false],
            ['name' => 'Martwy ciąg klasyczny', 'muscle_group' => 'plecy', 'lumbar_risk' => true],
            ['name' => 'Martwy ciąg na gumach/kettlebell', 'muscle_group' => 'plecy', 'lumbar_risk' => false],
            ['name' => 'Wiosłowanie sztangą w opadzie', 'muscle_group' => 'plecy', 'lumbar_risk' => true],
            ['name' => 'Hip thrust', 'muscle_group' => 'pośladki', 'lumbar_risk' => false],
            ['name' => 'Podciąganie na drążku', 'muscle_group' => 'plecy', 'lumbar_risk' => false],
            ['name' => 'Wykroki z hantlami', 'muscle_group' => 'nogi', 'lumbar_risk' => false],
            ['name' => 'Uginanie ramion ze sztangą', 'muscle_group' => 'biceps', 'lumbar_risk' => false],
            ['name' => 'Wyprosty ramion na wyciągu', 'muscle_group' => 'triceps', 'lumbar_risk' => false],
            ['name' => 'Plank', 'muscle_group' => 'core', 'lumbar_risk' => false],
        ];

        foreach ($exercises as $exercise) {
            Exercise::firstOrCreate(
                ['name' => $exercise['name']],
                [...$exercise, 'is_predefined' => true]
            );
        }
    }
}
