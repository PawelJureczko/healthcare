<?php

use App\Models\Run;
use App\Models\SportSession;
use App\Models\StravaConnection;
use App\Models\TrainingGoal;
use App\Models\User;
use App\Models\Workout;

test('a user never sees another users workouts, goals or strava connection via routes', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'run']);
    Run::factory()->for($workoutB)->create();
    TrainingGoal::factory()->for($userB)->create(['status' => 'active']);
    StravaConnection::factory()->for($userB)->create();

    $this->actingAs($userA);

    $this->get('/biegi')->assertInertia(fn ($page) => $page->has('runs', 0)->where('activeGoal', null));
    $this->get('/dashboard')->assertInertia(fn ($page) => $page
        ->where('running.activeGoal', null)
        ->where('running.stravaConnected', false));
});

test('a user cannot set a goal, log a run or sync strava on behalf of another account', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA)->post('/runs', [
        'date' => now()->format('Y-m-d'),
        'distance_km' => 5,
        'duration_min' => 30,
    ]);

    expect(Workout::where('user_id', $userB->id)->count())->toBe(0)
        ->and(Workout::where('user_id', $userA->id)->count())->toBe(1);
});

test('sport sessions are isolated the same way as runs', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'sport']);
    SportSession::factory()->for($workoutB)->create();

    $response = $this->actingAs($userA)->get('/sporty');

    $response->assertInertia(fn ($page) => $page->has('sessions', 0));
});

test('deleting a user cascades to their workouts, runs, goals and strava connection', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'run']);
    Run::factory()->for($workout)->create();
    TrainingGoal::factory()->for($user)->create();
    StravaConnection::factory()->for($user)->create();

    $user->delete();

    expect(Workout::withoutGlobalScopes()->count())->toBe(0)
        ->and(Run::count())->toBe(0)
        ->and(TrainingGoal::withoutGlobalScopes()->count())->toBe(0)
        ->and(StravaConnection::withoutGlobalScopes()->count())->toBe(0);
});
