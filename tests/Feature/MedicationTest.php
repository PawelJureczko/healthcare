<?php

use App\Models\Medication;
use App\Models\User;

test('a user can add a medication', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/medications', [
        'name' => 'Omega-3',
        'dose' => '1000 mg',
        'started_at' => '2026-01-01',
    ])->assertRedirect();

    $this->assertDatabaseHas('medications', ['user_id' => $user->id, 'name' => 'Omega-3']);
});

test('a user can mark their own medication as stopped', function () {
    $user = User::factory()->create();
    $medication = Medication::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch("/medications/{$medication->id}", ['stopped_at' => '2026-06-01'])
        ->assertRedirect();

    expect($medication->fresh()->stopped_at->toDateString())->toBe('2026-06-01');
});

test('a user cannot update another users medication', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $medicationB = Medication::factory()->for($userB)->create();

    $this->actingAs($userA)
        ->patch("/medications/{$medicationB->id}", ['stopped_at' => '2026-06-01'])
        ->assertStatus(404);
});
