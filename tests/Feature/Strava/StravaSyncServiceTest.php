<?php

use App\Models\Run;
use App\Models\StravaConnection;
use App\Models\Workout;
use App\Services\Strava\StravaSyncService;
use Illuminate\Support\Facades\Http;

function fakeStravaActivity(int $id, string $type = 'Run'): array
{
    return [
        'id' => $id,
        'type' => $type,
        'start_date_local' => '2026-09-01T07:00:00Z',
        'distance' => 5000.0,
        'moving_time' => 1500,
        'average_heartrate' => 150.0,
        'calories' => 400.0,
    ];
}

test('sync imports new activities across pages and stops on a short page', function () {
    $connection = StravaConnection::factory()->create(['last_synced_at' => null]);

    $fullPage = array_map(fn ($i) => fakeStravaActivity($i), range(1, 100));
    $lastPage = [fakeStravaActivity(101)];

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push($fullPage)
            ->push($lastPage),
    ]);

    $imported = app(StravaSyncService::class)->sync($connection);

    expect($imported)->toBe(101)
        ->and(Workout::count())->toBe(101)
        ->and(Run::count())->toBe(101)
        ->and($connection->fresh()->last_synced_at)->not->toBeNull();
});

test('sync assigns imported workouts to the connections owner', function () {
    $connection = StravaConnection::factory()->create(['last_synced_at' => null]);

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push([fakeStravaActivity(1)])
            ->push([]),
    ]);

    app(StravaSyncService::class)->sync($connection);

    expect(Workout::first()->user_id)->toBe($connection->user_id);
});

test('running sync twice with overlapping activities imports zero duplicates the second time', function () {
    $connection = StravaConnection::factory()->create(['last_synced_at' => null]);
    $activities = [fakeStravaActivity(1), fakeStravaActivity(2, 'Squash')];

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push($activities)
            ->push([]),
    ]);
    $firstImport = app(StravaSyncService::class)->sync($connection);

    // Second sync: Strava returns the SAME activities again (e.g. the API's
    // `after` cursor overlapping) — dedup must rely on strava_activity_id,
    // not on last_synced_at alone.
    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push($activities)
            ->push([]),
    ]);
    $secondImport = app(StravaSyncService::class)->sync($connection->fresh());

    expect($firstImport)->toBe(2)
        ->and($secondImport)->toBe(0)
        ->and(Workout::count())->toBe(2);
});

test('the sync route imports activities for the current users connection', function () {
    $user = \App\Models\User::factory()->create();
    \App\Models\StravaConnection::factory()->for($user)->create(['last_synced_at' => null]);

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::sequence()
            ->push([fakeStravaActivity(9)])
            ->push([]),
    ]);

    $this->actingAs($user)
        ->post('/integracje/strava/synchronizuj')
        ->assertRedirect();

    expect(Workout::where('user_id', $user->id)->count())->toBe(1);
});

test('the sync route is a no-op when the user has no strava connection', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->post('/integracje/strava/synchronizuj')
        ->assertRedirect();

    expect(Workout::count())->toBe(0);
});
