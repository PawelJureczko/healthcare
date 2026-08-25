<?php

use App\Models\BodyMeasurement;
use App\Models\User;
use App\Services\WeightTrend;
use Illuminate\Support\Carbon;

test('seven day average is the mean weight over the trailing 7 calendar days', function () {
    $user = User::factory()->create();
    $today = Carbon::parse('2026-08-25');

    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-24', 'weight_kg' => 92.0]);
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-19', 'weight_kg' => 94.0]);
    // Outside the trailing-7-day window (2026-08-18 is day 8 back) — must be excluded.
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-18', 'weight_kg' => 999.0]);

    expect(WeightTrend::sevenDayAverage($user, $today))->toBe(92.0);
});

test('seven day average is null when there is no data in the window', function () {
    $user = User::factory()->create();

    expect(WeightTrend::sevenDayAverage($user, Carbon::parse('2026-08-25')))->toBeNull();
});

test('weekly trend is the difference between this weeks average and last weeks', function () {
    $user = User::factory()->create();
    $today = Carbon::parse('2026-08-25');

    // This week (2026-08-19..25): avg 90.0
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);
    // Last week (2026-08-12..18): avg 90.4
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-18', 'weight_kg' => 90.4]);

    expect(WeightTrend::weeklyTrend($user, $today))->toBe(-0.4);
});

test('weekly trend is null when either window is missing data', function () {
    $user = User::factory()->create();
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);

    expect(WeightTrend::weeklyTrend($user, Carbon::parse('2026-08-25')))->toBeNull();
});
