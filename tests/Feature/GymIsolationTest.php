<?php

use App\Models\Exercise;
use App\Models\GymExercise;
use App\Models\GymSet;
use App\Models\User;
use App\Models\Workout;

test('a user never sees another users gym workouts via routes', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'gym']);
    GymExercise::factory()->for($workoutB)->create();

    $this->actingAs($userA);

    $this->get('/silownia')->assertInertia(fn ($page) => $page->has('workouts', 0));
    $this->get("/silownia/{$workoutB->id}")->assertNotFound();
    $this->get("/silownia/{$workoutB->id}/zakoncz")->assertNotFound();
});

test('a user cannot create a gym workout on behalf of another account', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $exercise = Exercise::factory()->create();

    $this->actingAs($userA)->post('/gym-workouts', [
        'date' => now()->format('Y-m-d'),
        'exercises' => [['exercise_id' => $exercise->id, 'sets' => [['planned_reps' => 5]]]],
    ]);

    expect(Workout::where('user_id', $userB->id)->where('type', 'gym')->count())->toBe(0)
        ->and(Workout::where('user_id', $userA->id)->where('type', 'gym')->count())->toBe(1);
});

test('exercise progression never mixes another users gym sets in, even for a shared exercise', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $exercise = Exercise::factory()->create();

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'gym']);
    $gymExerciseB = GymExercise::factory()->for($workoutB)->for($exercise)->create();
    GymSet::factory()->for($gymExerciseB)->create(['weight_kg' => 80, 'status' => 'done']);

    $response = $this->actingAs($userA)->get("/cwiczenia/{$exercise->id}/progresja");

    $response->assertInertia(fn ($page) => $page->has('sessions', 0));
});

test('deleting a user cascades to their gym workouts, gym exercises and gym sets, but not the shared exercise dictionary', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'gym']);
    $gymExercise = GymExercise::factory()->for($workout)->for($exercise)->create();
    GymSet::factory()->for($gymExercise)->create();

    $user->delete();

    expect(Workout::withoutGlobalScopes()->where('type', 'gym')->count())->toBe(0)
        ->and(GymExercise::count())->toBe(0)
        ->and(GymSet::count())->toBe(0)
        ->and(Exercise::count())->toBe(1);
});
