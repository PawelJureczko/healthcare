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

test('seven day average series computes a simple ascending run', function () {
    $measurements = [
        ['date' => '2026-08-19', 'weight_kg' => 90.0],
        ['date' => '2026-08-20', 'weight_kg' => 92.0],
        ['date' => '2026-08-21', 'weight_kg' => 94.0],
    ];

    expect(WeightTrend::sevenDayAverageSeries($measurements))->toBe([90.0, 91.0, 92.0]);
});

test('seven day average series drops entries older than 7 calendar days out of the window', function () {
    $measurements = [
        ['date' => '2026-08-19', 'weight_kg' => 100.0],
        ['date' => '2026-08-25', 'weight_kg' => 90.0],
        ['date' => '2026-08-26', 'weight_kg' => 92.0],
    ];

    // 2026-08-19 is 7 days before 2026-08-25 (still inside a trailing-7-day window
    // that includes 2026-08-19..25 inclusive), so it counts for that entry...
    expect(WeightTrend::sevenDayAverageSeries($measurements))->toBe([100.0, 95.0, 91.0]);
    // ...but by 2026-08-26 the window is 2026-08-20..26, so 08-19 drops out and only
    // the last two measurements (90.0, 92.0) remain -> average 91.0.
});

test('seven day average series handles a gap of more than 7 days between measurements', function () {
    $measurements = [
        ['date' => '2026-08-01', 'weight_kg' => 100.0],
        ['date' => '2026-08-20', 'weight_kg' => 90.0],
        ['date' => '2026-08-21', 'weight_kg' => 92.0],
    ];

    // Each entry's window only ever contains itself and the immediately preceding
    // measurement when that measurement falls within 6 days back.
    expect(WeightTrend::sevenDayAverageSeries($measurements))->toBe([100.0, 90.0, 91.0]);
});
