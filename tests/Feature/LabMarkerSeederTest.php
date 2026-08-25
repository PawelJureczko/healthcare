<?php

use App\Models\LabMarker;

test('seeding creates exactly the 8 predefined lab markers', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LabMarkerSeeder']);

    expect(LabMarker::where('is_predefined', true)->count())->toBe(8)
        ->and(LabMarker::where('name', 'LDL')->first()->norm_max)->toEqual(100.00);
});

test('re-seeding lab markers is idempotent', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LabMarkerSeeder']);
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LabMarkerSeeder']);

    expect(LabMarker::count())->toBe(8);
});
