<?php

namespace App\Services\Strava;

use Illuminate\Support\Str;

class StravaActivityMapper
{
    private const SPORT_TYPE_MAP = [
        'TableTennis' => 'table_tennis',
        'Squash' => 'squash',
    ];

    public static function map(array $activity): array
    {
        $isRun = ($activity['type'] ?? null) === 'Run';

        $workout = [
            'type' => $isRun ? 'run' : 'sport',
            'sport_subtype' => $isRun ? null : self::mapSportSubtype($activity),
            'date' => substr($activity['start_date_local'], 0, 10),
            'status' => 'completed',
        ];

        $distanceM = (int) round($activity['distance'] ?? 0);
        $durationS = (int) ($activity['moving_time'] ?? 0);
        $avgHeartRate = isset($activity['average_heartrate']) ? (int) round($activity['average_heartrate']) : null;
        $kcal = isset($activity['calories']) ? (int) round($activity['calories']) : null;

        if ($isRun) {
            $detail = [
                'distance_m' => $distanceM,
                'duration_s' => $durationS,
                'avg_pace_s_per_km' => $distanceM > 0 ? (int) round($durationS / ($distanceM / 1000)) : null,
                'avg_heart_rate' => $avgHeartRate,
                'max_heart_rate' => isset($activity['max_heartrate']) ? (int) round($activity['max_heartrate']) : null,
                'kcal' => $kcal,
                'strava_activity_id' => $activity['id'],
                'strava_raw' => $activity,
            ];
        } else {
            $detail = [
                'duration_s' => $durationS,
                'kcal' => $kcal,
                'avg_heart_rate' => $avgHeartRate,
                'intensity' => null,
                'strava_activity_id' => $activity['id'],
                'strava_raw' => $activity,
            ];
        }

        return ['workout' => $workout, 'detail' => $detail, 'table' => $isRun ? 'runs' : 'sport_sessions'];
    }

    private static function mapSportSubtype(array $activity): string
    {
        $type = $activity['sport_type'] ?? $activity['type'] ?? 'Other';

        return self::SPORT_TYPE_MAP[$type] ?? Str::snake($type);
    }
}
