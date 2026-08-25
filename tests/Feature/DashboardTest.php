<?php

use App\Models\BodyMeasurement;
use App\Models\Reminder;
use App\Models\User;

test('dashboard shows the weight seven day average and trend', function () {
    $user = User::factory()->create();
    BodyMeasurement::factory()->for($user)->create(['date' => now()->toDateString(), 'weight_kg' => 89.4]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('weight.sevenDayAverage', 89.4)
    );
});

test('dashboard shows the next lab reminder due date', function () {
    $user = User::factory()->create();
    Reminder::factory()->for($user)->create([
        'type' => 'Lipidogram',
        'interval_days' => 90,
        'last_completed_at' => now()->subDays(80)->toDateString(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('health.nextReminder.type', 'Lipidogram')
        ->where('health.nextReminder.days_until_due', 10)
    );
});

test('a never-completed reminder appears as the next reminder instead of being hidden', function () {
    $user = User::factory()->create();
    Reminder::factory()->for($user)->create([
        'type' => 'Lipidogram',
        'interval_days' => 90,
        'last_completed_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('health.nextReminder.type', 'Lipidogram')
        ->where('health.nextReminder.days_until_due', null)
    );
});

test('a never-completed reminder is more urgent than one with days remaining', function () {
    $user = User::factory()->create();
    Reminder::factory()->for($user)->create([
        'type' => 'Morfologia',
        'interval_days' => 90,
        'last_completed_at' => now()->subDays(10)->toDateString(),
    ]);
    Reminder::factory()->for($user)->create([
        'type' => 'Lipidogram',
        'interval_days' => 90,
        'last_completed_at' => null,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('health.nextReminder.type', 'Lipidogram')
        ->where('health.nextReminder.days_until_due', null)
    );
});

test('dashboard requires authentication', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});
