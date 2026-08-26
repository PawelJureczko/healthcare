<?php

namespace App\Services\Strava;

use App\Models\Run;
use App\Models\SportSession;
use App\Models\StravaConnection;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;

class StravaSyncService
{
    public function __construct(private readonly StravaClient $client)
    {
    }

    public function sync(StravaConnection $connection): int
    {
        $this->client->ensureFreshToken($connection);

        $after = $connection->last_synced_at?->timestamp;
        $imported = 0;
        $page = 1;

        do {
            $activities = $this->client->fetchActivitiesPage($connection, $after, $page);

            foreach ($activities as $activity) {
                if ($this->importActivity($connection->user_id, $activity)) {
                    $imported++;
                }
            }

            $page++;
        } while (count($activities) === 100);

        $connection->update(['last_synced_at' => now()]);

        return $imported;
    }

    private function importActivity(int $userId, array $activity): bool
    {
        $mapped = StravaActivityMapper::map($activity);
        $detailModel = $mapped['table'] === 'runs' ? Run::class : SportSession::class;

        if ($detailModel::where('strava_activity_id', $mapped['detail']['strava_activity_id'])->exists()) {
            return false;
        }

        DB::transaction(function () use ($userId, $mapped, $detailModel) {
            $workout = Workout::create([...$mapped['workout'], 'user_id' => $userId]);
            $detailModel::create([...$mapped['detail'], 'workout_id' => $workout->id]);
        });

        return true;
    }
}
