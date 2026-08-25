<?php

namespace App\Services;

use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Support\Carbon;

class WeightTrend
{
    public static function sevenDayAverage(User $user, ?Carbon $asOf = null): ?float
    {
        $asOf ??= Carbon::today();

        $average = BodyMeasurement::forUser($user)
            ->whereBetween('date', [
                $asOf->copy()->subDays(6)->toDateString(),
                $asOf->toDateString(),
            ])
            ->avg('weight_kg');

        return $average !== null ? round((float) $average, 1) : null;
    }

    public static function weeklyTrend(User $user, ?Carbon $asOf = null): ?float
    {
        $asOf ??= Carbon::today();

        $current = self::sevenDayAverage($user, $asOf);
        $previous = self::sevenDayAverage($user, $asOf->copy()->subDays(7));

        if ($current === null || $previous === null) {
            return null;
        }

        return round($current - $previous, 1);
    }
}
