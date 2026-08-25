<?php

use App\Models\BodyMeasurement;
use App\Models\User;

test('a user can log a new weight entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/body-measurements', [
            'date' => '2026-08-25',
            'weight_kg' => 89.5,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('body_measurements', [
        'user_id' => $user->id,
        'date' => '2026-08-25',
        'weight_kg' => 89.5,
    ]);
});

test('logging a second entry for the same date updates it instead of duplicating', function () {
    $user = User::factory()->create();
    BodyMeasurement::factory()->for($user)->create(['date' => '2026-08-25', 'weight_kg' => 90.0]);

    $this->actingAs($user)->post('/body-measurements', [
        'date' => '2026-08-25',
        'weight_kg' => 89.0,
    ]);

    expect(BodyMeasurement::where('user_id', $user->id)->where('date', '2026-08-25')->count())->toBe(1)
        ->and(BodyMeasurement::where('user_id', $user->id)->first()->weight_kg)->toEqual(89.0);
});

test('a user cannot see another users body measurements on the history page', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    BodyMeasurement::factory()->for($userB)->create(['weight_kg' => 77.0]);

    $response = $this->actingAs($userA)->get('/cialo');

    $response->assertInertia(fn ($page) => $page->component('Body/Index')->has('measurements', 0));
});

test('a guest cannot log a weight entry', function () {
    $this->post('/body-measurements', ['date' => '2026-08-25', 'weight_kg' => 80])
        ->assertRedirect('/login');
});
