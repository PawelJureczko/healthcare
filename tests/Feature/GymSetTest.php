<?php

use App\Models\GymExercise;
use App\Models\GymSet;
use App\Models\User;
use App\Models\Workout;

test('a user can mark their own gym set as done with actual weight and reps', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'gym']);
    $gymExercise = GymExercise::factory()->for($workout)->create();
    $gymSet = GymSet::factory()->for($gymExercise)->create(['status' => 'pending']);

    $this->actingAs($user)
        ->patchJson("/gym-sets/{$gymSet->id}", ['weight_kg' => 42.5, 'reps' => 10, 'status' => 'done'])
        ->assertOk();

    $gymSet->refresh();
    expect((float) $gymSet->weight_kg)->toBe(42.5)
        ->and($gymSet->reps)->toBe(10)
        ->and($gymSet->status)->toBe('done');
});

test('a user cannot update another users gym set', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $workout = Workout::factory()->for($owner)->create(['type' => 'gym']);
    $gymExercise = GymExercise::factory()->for($workout)->create();
    $gymSet = GymSet::factory()->for($gymExercise)->create(['status' => 'pending']);

    $this->actingAs($attacker)
        ->patchJson("/gym-sets/{$gymSet->id}", ['weight_kg' => 50, 'reps' => 1, 'status' => 'done'])
        ->assertNotFound();

    expect($gymSet->fresh()->status)->toBe('pending');
});

test('status must be one of pending, done or skipped', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'gym']);
    $gymExercise = GymExercise::factory()->for($workout)->create();
    $gymSet = GymSet::factory()->for($gymExercise)->create();

    $this->actingAs($user)
        ->patchJson("/gym-sets/{$gymSet->id}", ['status' => 'invalid-status'])
        ->assertStatus(422);
});

test('a guest cannot update a gym set', function () {
    $gymSet = GymSet::factory()->create();

    $this->patchJson("/gym-sets/{$gymSet->id}", ['status' => 'done'])->assertUnauthorized();
});

test('the show page includes gym exercises, sets and previous-weight hints, scoped to the owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $workout = Workout::factory()->for($owner)->create(['type' => 'gym']);
    $gymExercise = GymExercise::factory()->for($workout)->create();
    GymSet::factory()->for($gymExercise)->create();

    $this->actingAs($other)->get("/silownia/{$workout->id}")->assertNotFound();

    $response = $this->actingAs($owner)->get("/silownia/{$workout->id}");
    $response->assertInertia(fn ($page) => $page
        ->component('GymWorkouts/Show')
        ->has('workout.gymExercises', 1)
        ->has('workout.gymExercises.0.gymSets', 1));
});
