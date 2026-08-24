<?php

use App\Models\User;

test('exactly two user accounts exist after seeding', function () {
    $this->artisan('db:seed');

    expect(User::count())->toBe(2);
});

test('both seeded accounts can authenticate', function () {
    $this->artisan('db:seed');

    $this->post('/login', [
        'email' => env('SEED_USER_ONE_EMAIL', 'user1@centrum.local'),
        'password' => env('SEED_USER_ONE_PASSWORD', 'password'),
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    auth()->logout();

    $this->post('/login', [
        'email' => env('SEED_USER_TWO_EMAIL', 'user2@centrum.local'),
        'password' => env('SEED_USER_TWO_PASSWORD', 'password'),
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
