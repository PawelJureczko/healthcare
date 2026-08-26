<?php

use App\Models\User;
use App\Models\Workout;

test('a user can finish their gym workout with back pain, wellbeing and a comment', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'gym', 'status' => 'planned']);

    $this->actingAs($user)->post("/silownia/{$workout->id}/zakoncz", [
        'back_pain_rating' => 3,
        'wellbeing_rating' => 4,
        'comment' => 'Kolano trochę ciągnęło przy przysiadzie',
    ])->assertRedirect(route('gym-workouts.index'));

    $workout->refresh();
    expect($workout->status)->toBe('completed')
        ->and($workout->back_pain_rating)->toBe(3)
        ->and($workout->wellbeing_rating)->toBe(4)
        ->and($workout->comment)->toBe('Kolano trochę ciągnęło przy przysiadzie');
});

test('back_pain_rating must be between 0 and 10', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['type' => 'gym']);

    $this->actingAs($user)
        ->post("/silownia/{$workout->id}/zakoncz", ['back_pain_rating' => 11, 'wellbeing_rating' => 3])
        ->assertSessionHasErrors('back_pain_rating');
});

test('a user cannot finish another users workout', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $workout = Workout::factory()->for($owner)->create(['type' => 'gym']);

    $this->actingAs($attacker)
        ->post("/silownia/{$workout->id}/zakoncz", ['back_pain_rating' => 2, 'wellbeing_rating' => 3])
        ->assertNotFound();
});

test('a run workout cannot be viewed or finished via the gym endpoints', function () {
    $user = User::factory()->create();
    $runWorkout = Workout::factory()->for($user)->create(['type' => 'run', 'status' => 'planned']);

    $this->actingAs($user)->get("/silownia/{$runWorkout->id}")->assertNotFound();

    $this->actingAs($user)
        ->post("/silownia/{$runWorkout->id}/zakoncz", ['back_pain_rating' => 2, 'wellbeing_rating' => 3])
        ->assertNotFound();

    $runWorkout->refresh();
    expect($runWorkout->status)->toBe('planned');
});
