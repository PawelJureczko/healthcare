<?php

use App\Models\Run;
use App\Models\User;
use App\Models\Workout;

test('a user can manually log a run', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/runs', [
        'date' => '2026-08-20',
        'distance_km' => 5.0,
        'duration_min' => 30,
        'avg_heart_rate' => 150,
        'comment' => 'Czułem się mocny',
        'wellbeing_rating' => 4,
    ])->assertRedirect(route('runs.index'));

    $workout = Workout::where('user_id', $user->id)->where('type', 'run')->first();

    expect($workout)->not->toBeNull()
        ->and($workout->comment)->toBe('Czułem się mocny')
        ->and($workout->wellbeing_rating)->toBe(4);

    $run = Run::where('workout_id', $workout->id)->first();
    expect($run->distance_m)->toBe(5000)
        ->and($run->duration_s)->toBe(1800)
        ->and($run->avg_pace_s_per_km)->toBe(360)
        ->and($run->strava_activity_id)->toBeNull();
});

test('distance and duration are required and validated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/runs', ['date' => '2026-08-20'])
        ->assertSessionHasErrors(['distance_km', 'duration_min']);

    expect(Workout::count())->toBe(0);
});

test('a guest cannot log a run', function () {
    $this->post('/runs', ['date' => '2026-08-20', 'distance_km' => 5, 'duration_min' => 30])
        ->assertRedirect('/login');
});

test('the runs index shows only the current users runs, newest data included, marking source', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutA = Workout::factory()->for($userA)->create(['type' => 'run', 'date' => '2026-08-20']);
    Run::factory()->for($workoutA)->create(['distance_m' => 5000, 'duration_s' => 1800, 'strava_activity_id' => 987]);

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'run']);
    Run::factory()->for($workoutB)->create();

    $response = $this->actingAs($userA)->get('/biegi');

    $response->assertInertia(fn ($page) => $page
        ->component('Runs/Index')
        ->has('runs', 1)
        ->where('runs.0.distance_km', 5)
        ->where('runs.0.source', 'strava')
        ->where('stravaConnected', false));
});
