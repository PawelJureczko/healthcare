<?php

use App\Models\Exercise;
use App\Models\GymExercise;
use App\Models\GymSet;
use App\Models\User;
use App\Models\Workout;

test('a gym exercise belongs to its workout and exercise, with no user_id column of its own', function () {
    $workout = Workout::factory()->create(['type' => 'gym']);
    $exercise = Exercise::factory()->create();
    $gymExercise = GymExercise::factory()->for($workout)->for($exercise)->create();

    expect($gymExercise->workout->id)->toBe($workout->id)
        ->and($gymExercise->exercise->id)->toBe($exercise->id)
        ->and($gymExercise->getAttributes())->not->toHaveKey('user_id');
});

test('a gym set belongs to its gym exercise, with no user_id column of its own', function () {
    $gymExercise = GymExercise::factory()->create();
    $gymSet = GymSet::factory()->for($gymExercise)->create();

    expect($gymSet->gymExercise->id)->toBe($gymExercise->id)
        ->and($gymSet->getAttributes())->not->toHaveKey('user_id');
});

test('gym exercise isolation is inherited transitively through workout_id', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutA = Workout::factory()->for($userA)->create(['type' => 'gym']);
    $workoutB = Workout::factory()->for($userB)->create(['type' => 'gym']);
    GymExercise::factory()->for($workoutA)->create();
    GymExercise::factory()->for($workoutB)->create();

    $this->actingAs($userA);

    // GymExercise has no scope of its own — documents the expected,
    // safe-by-design behavior: callers MUST filter through Workout::forUser().
    expect(GymExercise::count())->toBe(2);

    $isolated = Workout::forUser($userA)->where('type', 'gym')->with('gymExercises')->get();
    expect($isolated)->toHaveCount(1)
        ->and($isolated->first()->gymExercises)->toHaveCount(1);
});

test('exercises are a shared dictionary visible to both users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Exercise::factory()->create(['name' => 'Przysiad testowy']);

    $this->actingAs($userA);
    expect(Exercise::count())->toBe(1);

    $this->actingAs($userB);
    expect(Exercise::count())->toBe(1);
});
