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

    /**
     * Compute a trailing 7-calendar-day average for a chart series.
     *
     * @param  array<int, array{date: string, weight_kg: float}>  $measurements  Sorted ascending by date.
     * @return array<int, float> Same length as $measurements, one average per entry.
     */
    public static function sevenDayAverageSeries(array $measurements): array
    {
        $dates = array_map(fn ($m) => Carbon::parse($m['date']), $measurements);

        $result = [];
        $windowStart = 0;

        for ($i = 0; $i < count($measurements); $i++) {
            $windowFrom = $dates[$i]->copy()->subDays(6);

            while ($dates[$windowStart]->lt($windowFrom)) {
                $windowStart++;
            }

            $sum = 0.0;
            $count = 0;
            for ($j = $windowStart; $j <= $i; $j++) {
                $sum += (float) $measurements[$j]['weight_kg'];
                $count++;
            }

            $result[] = round($sum / $count, 1);
        }

        return $result;
    }
}
