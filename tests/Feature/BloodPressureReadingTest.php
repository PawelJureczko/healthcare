<?php

use App\Models\BloodPressureReading;
use App\Models\User;

test('a user can log a blood pressure reading', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/blood-pressure-readings', [
            'measured_at' => '2026-08-25 08:00:00',
            'systolic' => 125,
            'diastolic' => 80,
            'resting_pulse' => 62,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('blood_pressure_readings', [
        'user_id' => $user->id,
        'systolic' => 125,
        'diastolic' => 80,
    ]);
});

test('a user only sees their own readings on the history page', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    BloodPressureReading::factory()->for($userB)->create();

    $response = $this->actingAs($userA)->get('/cisnienie');

    $response->assertInertia(fn ($page) => $page->component('Health/BloodPressure')->has('readings', 0));
});

test('a guest cannot log a blood pressure reading', function () {
    $this->post('/blood-pressure-readings', ['measured_at' => now(), 'systolic' => 120, 'diastolic' => 80])
        ->assertRedirect('/login');
});
