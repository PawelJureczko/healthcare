<?php

use App\Models\Reminder;
use App\Models\User;

test('a user can create a reminder', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/reminders', [
        'type' => 'Lipidogram',
        'interval_days' => 90,
    ])->assertRedirect();

    $this->assertDatabaseHas('reminders', ['user_id' => $user->id, 'type' => 'Lipidogram']);
});

test('marking a reminder done sets last_completed_at to today', function () {
    $user = User::factory()->create();
    $reminder = Reminder::factory()->for($user)->create(['last_completed_at' => null]);

    $this->actingAs($user)->patch("/reminders/{$reminder->id}")->assertRedirect();

    expect($reminder->fresh()->last_completed_at->toDateString())->toBe(now()->toDateString());
});

test('a user cannot mark another users reminder as done', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $reminderB = Reminder::factory()->for($userB)->create();

    $this->actingAs($userA)->patch("/reminders/{$reminderB->id}")->assertStatus(404);
});
