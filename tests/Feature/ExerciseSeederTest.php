<?php

use App\Models\Exercise;
use Database\Seeders\ExerciseSeeder;

test('seeding twice does not duplicate exercises', function () {
    (new ExerciseSeeder)->run();
    $countAfterFirst = Exercise::count();

    (new ExerciseSeeder)->run();

    expect(Exercise::count())->toBe($countAfterFirst)
        ->and($countAfterFirst)->toBe(13);
});

test('seeded exercises include at least one lumbar-risk flagged exercise and a safer alternative', function () {
    (new ExerciseSeeder)->run();

    expect(Exercise::where('lumbar_risk', true)->count())->toBeGreaterThanOrEqual(2)
        ->and(Exercise::where('name', 'Martwy ciąg na gumach/kettlebell')->where('lumbar_risk', false)->exists())->toBeTrue()
        ->and(Exercise::where('name', 'Wiosłowanie na maszynie')->where('lumbar_risk', false)->exists())->toBeTrue();
});
