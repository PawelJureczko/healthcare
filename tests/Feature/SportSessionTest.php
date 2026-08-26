<?php

use App\Models\SportSession;
use App\Models\User;
use App\Models\Workout;

test('a user can manually log a sport session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/sport-sessions', [
        'date' => '2026-08-20',
        'sport_subtype' => 'squash',
        'duration_min' => 60,
        'intensity' => 4,
        'comment' => 'Mecz z Michałem',
    ])->assertRedirect(route('sport-sessions.index'));

    $workout = Workout::where('user_id', $user->id)->where('type', 'sport')->first();

    expect($workout)->not->toBeNull()
        ->and($workout->sport_subtype)->toBe('squash')
        ->and($workout->comment)->toBe('Mecz z Michałem');

    $session = SportSession::where('workout_id', $workout->id)->first();
    expect($session->duration_s)->toBe(3600)
        ->and($session->intensity)->toBe(4);
});

test('sport_subtype must be one of the known dictionary values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/sport-sessions', ['date' => '2026-08-20', 'sport_subtype' => 'nieznany', 'duration_min' => 60, 'intensity' => 3])
        ->assertSessionHasErrors('sport_subtype');
});

test('a guest cannot log a sport session', function () {
    $this->post('/sport-sessions', ['date' => '2026-08-20', 'sport_subtype' => 'squash', 'duration_min' => 60, 'intensity' => 3])
        ->assertRedirect('/login');
});

test('the sports index shows only the current users sessions', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $workoutA = Workout::factory()->for($userA)->create(['type' => 'sport', 'sport_subtype' => 'table_tennis']);
    SportSession::factory()->for($workoutA)->create(['duration_s' => 1800, 'intensity' => 3]);

    $workoutB = Workout::factory()->for($userB)->create(['type' => 'sport']);
    SportSession::factory()->for($workoutB)->create();

    $response = $this->actingAs($userA)->get('/sporty');

    $response->assertInertia(fn ($page) => $page
        ->component('Sports/Index')
        ->has('sessions', 1)
        ->where('sessions.0.sport_subtype', 'table_tennis'));
});
