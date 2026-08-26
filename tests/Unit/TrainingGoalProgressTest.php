<?php

use App\Models\Run;
use App\Models\TrainingGoal;
use App\Models\User;
use App\Models\Workout;
use App\Services\TrainingGoalProgress;
use Illuminate\Support\Carbon;

test('percent is the ratio of the longest run since goal creation to the target, capped at 100', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 10000,
        'created_at' => Carbon::parse('2026-08-01'),
    ]);

    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-15']);
    Run::factory()->for($workout)->create(['distance_m' => 6000]);

    expect(TrainingGoalProgress::percent($goal))->toBe(60);
});

test('percent ignores runs logged before the goal was created', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 10000,
        'created_at' => Carbon::parse('2026-08-15'),
    ]);

    $oldWorkout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-01']);
    Run::factory()->for($oldWorkout)->create(['distance_m' => 9000]);

    expect(TrainingGoalProgress::percent($goal))->toBe(0);
});

test('percent is capped at 100 even when the longest run exceeds the target', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 5000,
        'created_at' => Carbon::parse('2026-08-01'),
    ]);

    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-10']);
    Run::factory()->for($workout)->create(['distance_m' => 8000]);

    expect(TrainingGoalProgress::percent($goal))->toBe(100);
});

test('percent is zero when there are no qualifying runs', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create();

    expect(TrainingGoalProgress::percent($goal))->toBe(0);
});

test('refreshStatus marks an active goal achieved once the target distance is reached', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 5000,
        'status' => 'active',
        'created_at' => Carbon::parse('2026-08-01'),
    ]);
    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-10']);
    Run::factory()->for($workout)->create(['distance_m' => 5000]);

    TrainingGoalProgress::refreshStatus($goal);

    expect($goal->fresh()->status)->toBe('achieved');
});

test('refreshStatus leaves an already-abandoned goal untouched', function () {
    $user = User::factory()->create();
    $goal = TrainingGoal::factory()->for($user)->create([
        'target_distance_m' => 5000,
        'status' => 'abandoned',
        'created_at' => Carbon::parse('2026-08-01'),
    ]);
    $workout = Workout::factory()->for($user)->create(['type' => 'run', 'date' => '2026-08-10']);
    Run::factory()->for($workout)->create(['distance_m' => 9000]);

    TrainingGoalProgress::refreshStatus($goal);

    expect($goal->fresh()->status)->toBe('abandoned');
});
