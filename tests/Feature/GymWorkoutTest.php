<?php

use App\Models\Exercise;
use App\Models\GymExercise;
use App\Models\GymSet;
use App\Models\User;
use App\Models\Workout;

test('a user can create a gym workout with exercises and planned sets', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();

    $response = $this->actingAs($user)->post('/gym-workouts', [
        'date' => '2026-08-26',
        'exercises' => [
            [
                'exercise_id' => $exercise->id,
                'sets' => [
                    ['planned_weight_kg' => 40, 'planned_reps' => 10],
                    ['planned_weight_kg' => 45, 'planned_reps' => 8],
                ],
            ],
        ],
    ]);

    $workout = Workout::where('user_id', $user->id)->where('type', 'gym')->first();
    $response->assertRedirect("/silownia/{$workout->id}");

    expect($workout->status)->toBe('planned');

    $gymExercise = GymExercise::where('workout_id', $workout->id)->first();
    expect($gymExercise->exercise_id)->toBe($exercise->id)
        ->and($gymExercise->order)->toBe(0);

    $sets = GymSet::where('gym_exercise_id', $gymExercise->id)->orderBy('set_number')->get();
    expect($sets)->toHaveCount(2)
        ->and($sets[0]->set_number)->toBe(1)
        ->and((float) $sets[0]->planned_weight_kg)->toBe(40.0)
        ->and($sets[0]->status)->toBe('pending')
        ->and($sets[1]->set_number)->toBe(2);
});

test('at least one exercise with at least one set is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/gym-workouts', ['date' => '2026-08-26', 'exercises' => []])
        ->assertSessionHasErrors('exercises');
});

test('a guest cannot create a gym workout', function () {
    $this->post('/gym-workouts', ['date' => '2026-08-26', 'exercises' => []])
        ->assertRedirect('/login');
});

test('the gym workout index lists only the current users workouts, newest first', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Workout::factory()->for($userA)->create(['type' => 'gym', 'date' => '2026-08-01', 'status' => 'completed', 'back_pain_rating' => 2]);
    Workout::factory()->for($userA)->create(['type' => 'gym', 'date' => '2026-08-15', 'status' => 'planned']);
    Workout::factory()->for($userB)->create(['type' => 'gym', 'date' => '2026-08-10']);

    $response = $this->actingAs($userA)->get('/silownia');

    $response->assertInertia(fn ($page) => $page
        ->component('GymWorkouts/Index')
        ->has('workouts', 2)
        ->where('workouts.0.date', '2026-08-15')
        ->where('workouts.1.back_pain_rating', 2));
});
