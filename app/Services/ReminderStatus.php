<?php

namespace App\Services;

use App\Models\Reminder;
use Illuminate\Support\Carbon;

class ReminderStatus
{
    public static function daysUntilDue(Reminder $reminder, ?Carbon $asOf = null): ?int
    {
        if ($reminder->last_completed_at === null) {
            return null;
        }

        $asOf ??= Carbon::today();
        $dueDate = $reminder->last_completed_at->copy()->addDays($reminder->interval_days);

        return (int) $asOf->diffInDays($dueDate, false);
    }
}
