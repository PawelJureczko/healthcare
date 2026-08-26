<?php

namespace App\Services\Strava;

use App\Models\Run;
use App\Models\SportSession;
use App\Models\StravaConnection;
use App\Models\TrainingGoal;
use App\Models\Workout;
use App\Services\TrainingGoalProgress;
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

        TrainingGoal::forUser($connection->user_id)
            ->where('type', 'run_distance')
            ->where('status', 'active')
            ->get()
            ->each(fn ($goal) => TrainingGoalProgress::refreshStatus($goal));

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
