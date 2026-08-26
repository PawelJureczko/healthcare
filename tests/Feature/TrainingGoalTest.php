<?php

use App\Models\TrainingGoal;
use App\Models\User;

test('a user can set a new active run distance goal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/cele-biegowe', [
        'target_distance_km' => 7.5,
        'target_date' => now()->addMonths(2)->format('Y-m-d'),
    ])->assertRedirect(route('runs.index'));

    $goal = TrainingGoal::where('user_id', $user->id)->where('status', 'active')->first();
    expect($goal)->not->toBeNull()
        ->and($goal->target_distance_m)->toBe(7500)
        ->and($goal->type)->toBe('run_distance');
});

test('setting a new goal abandons the previous active one', function () {
    $user = User::factory()->create();
    $oldGoal = TrainingGoal::factory()->for($user)->create(['status' => 'active']);

    $this->actingAs($user)->post('/cele-biegowe', [
        'target_distance_km' => 10,
        'target_date' => now()->addMonths(3)->format('Y-m-d'),
    ]);

    expect($oldGoal->fresh()->status)->toBe('abandoned')
        ->and(TrainingGoal::where('user_id', $user->id)->where('status', 'active')->count())->toBe(1);
});

test('target date must be in the future', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/cele-biegowe', ['target_distance_km' => 5, 'target_date' => now()->subDay()->format('Y-m-d')])
        ->assertSessionHasErrors('target_date');
});

test('the runs index exposes the active goal with computed progress', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 10000,
        'target_date' => '2026-12-01',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get('/biegi');

    $response->assertInertia(fn ($page) => $page
        ->where('activeGoal.target_distance_km', 10)
        ->where('activeGoal.target_date', '2026-12-01')
        ->where('activeGoal.progressPercent', 0));
});

test('logging a run that reaches the target auto-marks the goal achieved', function () {
    $user = User::factory()->create();
    TrainingGoal::factory()->for($user)->create(['target_distance_m' => 5000, 'status' => 'active']);

    $this->actingAs($user)->post('/runs', [
        'date' => now()->format('Y-m-d'),
        'distance_km' => 5.0,
        'duration_min' => 30,
    ]);

    expect(TrainingGoal::where('user_id', $user->id)->first()->status)->toBe('achieved');
});
