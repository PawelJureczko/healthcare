<?php

use App\Models\Run;
use App\Models\SportSession;
use App\Models\User;
use App\Models\Workout;

test('a workout is auto-scoped to the authenticated user and isolated from others', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Workout::factory()->for($userA)->create();
    Workout::factory()->for($userB)->create();

    $this->actingAs($userA);

    expect(Workout::count())->toBe(1)
        ->and(Workout::first()->user_id)->toBe($userA->id);
});

test('a run belongs to its workout and has no user_id column of its own', function () {
    $workout = Workout::factory()->create(['type' => 'run']);
    $run = Run::factory()->for($workout)->create();

    expect($run->workout->id)->toBe($workout->id)
        ->and($run->getAttributes())->not->toHaveKey('user_id');
});

test('a sport session belongs to its workout and has no user_id column of its own', function () {
    $workout = Workout::factory()->create(['type' => 'sport', 'sport_subtype' => 'squash']);
    $session = SportSession::factory()->for($workout)->create();

    expect($session->workout->id)->toBe($workout->id)
        ->and($session->getAttributes())->not->toHaveKey('user_id');
});

test('run isolation is inherited transitively through workout_id, not a global scope of its own', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutA = Workout::factory()->for($userA)->create(['type' => 'run']);
    $workoutB = Workout::factory()->for($userB)->create(['type' => 'run']);
    Run::factory()->for($workoutA)->create();
    Run::factory()->for($workoutB)->create();

    $this->actingAs($userA);

    // Run itself has no scope — this documents the expected (safe-by-design)
    // behavior: callers MUST filter through Workout::forUser(), never query
    // Run directly in a user-facing path.
    expect(Run::count())->toBe(2);

    $isolatedRuns = Workout::forUser($userA)->where('type', 'run')->with('run')->get();
    expect($isolatedRuns)->toHaveCount(1)
        ->and($isolatedRuns->first()->user_id)->toBe($userA->id);
});
