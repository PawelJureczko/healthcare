<?php

use App\Models\Exercise;
use App\Models\GymExercise;
use App\Models\GymSet;
use App\Models\User;
use App\Models\Workout;

test('progression shows the heaviest completed set per workout, chronologically, for the current user only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $exercise = Exercise::factory()->create();

    $workout1 = Workout::factory()->for($user)->create(['type' => 'gym', 'date' => '2026-08-01']);
    $gymExercise1 = GymExercise::factory()->for($workout1)->for($exercise)->create();
    GymSet::factory()->for($gymExercise1)->create(['weight_kg' => 40, 'status' => 'done']);
    GymSet::factory()->for($gymExercise1)->create(['weight_kg' => 45, 'status' => 'done', 'set_number' => 2]);

    $workout2 = Workout::factory()->for($user)->create(['type' => 'gym', 'date' => '2026-08-15']);
    $gymExercise2 = GymExercise::factory()->for($workout2)->for($exercise)->create();
    GymSet::factory()->for($gymExercise2)->create(['weight_kg' => 50, 'status' => 'done']);

    // Another user's data for the same exercise must never leak in.
    $otherWorkout = Workout::factory()->for($other)->create(['type' => 'gym', 'date' => '2026-08-10']);
    $otherGymExercise = GymExercise::factory()->for($otherWorkout)->for($exercise)->create();
    GymSet::factory()->for($otherGymExercise)->create(['weight_kg' => 999, 'status' => 'done']);

    $response = $this->actingAs($user)->get("/cwiczenia/{$exercise->id}/progresja");

    $response->assertInertia(fn ($page) => $page
        ->component('Exercises/Progression')
        ->has('sessions', 2)
        ->where('sessions.0.date', '2026-08-01')
        // PHP's json_encode collapses whole-number floats (45.0 -> "45"), so json_decode
        // on the round trip through Inertia's test helper yields int(45), not float(45.0).
        // A closure sidesteps that strict-type mismatch while still asserting the value.
        ->where('sessions.0.maxWeightKg', fn ($value) => $value == 45)
        ->where('sessions.1.date', '2026-08-15')
        ->where('sessions.1.maxWeightKg', fn ($value) => $value == 50));
});

test('progression is empty when the user never performed this exercise', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();

    $response = $this->actingAs($user)->get("/cwiczenia/{$exercise->id}/progresja");

    $response->assertInertia(fn ($page) => $page->has('sessions', 0));
});
