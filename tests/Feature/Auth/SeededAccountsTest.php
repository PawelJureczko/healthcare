<?php

use App\Models\User;

test('exactly two user accounts exist after seeding', function () {
    $this->artisan('db:seed');

    expect(User::count())->toBe(2);
});

test('both seeded accounts can authenticate', function () {
    $this->artisan('db:seed');

    $this->post('/login', [
        'email' => config('seed.user_one.email'),
        'password' => config('seed.user_one.password'),
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    auth()->logout();

    $this->post('/login', [
        'email' => config('seed.user_two.email'),
        'password' => config('seed.user_two.password'),
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('seeding twice stays idempotent and still results in exactly two users', function () {
    $this->artisan('db:seed');
    $this->artisan('db:seed');

    expect(User::count())->toBe(2);
});

test('seeded accounts land on the dashboard, not an email verification prompt', function () {
    $this->artisan('db:seed');

    $this->post('/login', [
        'email' => config('seed.user_one.email'),
        'password' => config('seed.user_one.password'),
    ]);

    $this->get(route('dashboard'))->assertOk();
});
