<?php

use App\Services\Strava\StravaActivityMapper;

test('maps a Run activity into a run workout with computed pace', function () {
    $activity = [
        'id' => 123456,
        'type' => 'Run',
        'start_date_local' => '2026-09-01T07:15:00Z',
        'distance' => 7500.0,
        'moving_time' => 2400,
        'average_heartrate' => 152.4,
        'max_heartrate' => 178.0,
        'calories' => 520.0,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['table'])->toBe('runs')
        ->and($mapped['workout'])->toBe([
            'type' => 'run',
            'sport_subtype' => null,
            'date' => '2026-09-01',
            'status' => 'completed',
        ])
        ->and($mapped['detail']['distance_m'])->toBe(7500)
        ->and($mapped['detail']['duration_s'])->toBe(2400)
        ->and($mapped['detail']['avg_pace_s_per_km'])->toBe(320)
        ->and($mapped['detail']['avg_heart_rate'])->toBe(152)
        ->and($mapped['detail']['max_heart_rate'])->toBe(178)
        ->and($mapped['detail']['kcal'])->toBe(520)
        ->and($mapped['detail']['strava_activity_id'])->toBe(123456)
        ->and($mapped['detail']['strava_raw'])->toBe($activity);
});

test('maps a TableTennis activity into a sport workout with known subtype', function () {
    $activity = [
        'id' => 777,
        'type' => 'Workout',
        'sport_type' => 'TableTennis',
        'start_date_local' => '2026-09-02T18:00:00Z',
        'distance' => 0,
        'moving_time' => 3600,
        'calories' => 300.0,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['table'])->toBe('sport_sessions')
        ->and($mapped['workout']['sport_subtype'])->toBe('table_tennis')
        ->and($mapped['detail']['duration_s'])->toBe(3600)
        ->and($mapped['detail']['intensity'])->toBeNull()
        ->and($mapped['detail']['strava_activity_id'])->toBe(777);
});

test('maps an unrecognized sport type to a snake_case fallback subtype', function () {
    $activity = [
        'id' => 42,
        'type' => 'Workout',
        'sport_type' => 'RockClimbing',
        'start_date_local' => '2026-09-03T10:00:00Z',
        'distance' => 0,
        'moving_time' => 1800,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['workout']['sport_subtype'])->toBe('rock_climbing');
});

test('missing optional fields (no heart rate sensor) map to null, not zero', function () {
    $activity = [
        'id' => 55,
        'type' => 'Run',
        'start_date_local' => '2026-09-04T06:00:00Z',
        'distance' => 5000.0,
        'moving_time' => 1500,
    ];

    $mapped = StravaActivityMapper::map($activity);

    expect($mapped['detail']['avg_heart_rate'])->toBeNull()
        ->and($mapped['detail']['max_heart_rate'])->toBeNull()
        ->and($mapped['detail']['kcal'])->toBeNull();
});
