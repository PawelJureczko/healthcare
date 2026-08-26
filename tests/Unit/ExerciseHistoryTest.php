<?php

use App\Models\Exercise;
use App\Models\GymExercise;
use App\Models\GymSet;
use App\Models\User;
use App\Models\Workout;
use App\Services\ExerciseHistory;

test('lastWeights returns the heaviest completed set from the most recent workout containing that exercise', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();

    $olderWorkout = Workout::factory()->for($user)->create(['type' => 'gym', 'date' => '2026-08-01']);
    $olderGymExercise = GymExercise::factory()->for($olderWorkout)->for($exercise)->create();
    GymSet::factory()->for($olderGymExercise)->create(['weight_kg' => 40, 'status' => 'done']);

    $newerWorkout = Workout::factory()->for($user)->create(['type' => 'gym', 'date' => '2026-08-15']);
    $newerGymExercise = GymExercise::factory()->for($newerWorkout)->for($exercise)->create();
    GymSet::factory()->for($newerGymExercise)->create(['weight_kg' => 45, 'status' => 'done']);
    GymSet::factory()->for($newerGymExercise)->create(['weight_kg' => 50, 'status' => 'done', 'set_number' => 2]);

    expect(ExerciseHistory::lastWeights($user, [$exercise->id]))->toBe([$exercise->id => 50.0]);
});

test('lastWeights ignores sets that are not done', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'gym']);
    $gymExercise = GymExercise::factory()->for($workout)->for($exercise)->create();
    GymSet::factory()->for($gymExercise)->create(['weight_kg' => 60, 'status' => 'pending']);

    expect(ExerciseHistory::lastWeights($user, [$exercise->id]))->toBe([$exercise->id => null]);
});

test('lastWeights returns null for an exercise never performed by this user', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();

    expect(ExerciseHistory::lastWeights($user, [$exercise->id]))->toBe([$exercise->id => null]);
});

test('lastWeights never mixes in another users history', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $exercise = Exercise::factory()->create();

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'gym']);
    $gymExerciseB = GymExercise::factory()->for($workoutB)->for($exercise)->create();
    GymSet::factory()->for($gymExerciseB)->create(['weight_kg' => 100, 'status' => 'done']);

    expect(ExerciseHistory::lastWeights($userA, [$exercise->id]))->toBe([$exercise->id => null]);
});
