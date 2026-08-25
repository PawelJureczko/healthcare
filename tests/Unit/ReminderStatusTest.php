<?php

use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderStatus;
use Illuminate\Support\Carbon;

test('days until due counts down from last completion plus the interval', function () {
    $reminder = Reminder::factory()->make([
        'interval_days' => 90,
        'last_completed_at' => '2026-06-01',
    ]);

    expect(ReminderStatus::daysUntilDue($reminder, Carbon::parse('2026-08-01')))->toBe(29);
});

test('days until due is negative when overdue', function () {
    $reminder = Reminder::factory()->make([
        'interval_days' => 90,
        'last_completed_at' => '2026-01-01',
    ]);

    expect(ReminderStatus::daysUntilDue($reminder, Carbon::parse('2026-08-01')))->toBe(-122);
});

test('days until due is null when the reminder has never been completed', function () {
    $reminder = Reminder::factory()->make(['last_completed_at' => null]);

    expect(ReminderStatus::daysUntilDue($reminder, Carbon::parse('2026-08-01')))->toBeNull();
});
