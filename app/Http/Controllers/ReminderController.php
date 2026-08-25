<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReminderRequest;
use App\Models\Reminder;
use App\Services\ReminderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReminderController extends Controller
{
    public function index(Request $request): Response
    {
        $reminders = $request->user()->reminders()->get()->map(fn (Reminder $reminder) => [
            'id' => $reminder->id,
            'type' => $reminder->type,
            'interval_days' => $reminder->interval_days,
            'last_completed_at' => $reminder->last_completed_at?->toDateString(),
            'days_until_due' => ReminderStatus::daysUntilDue($reminder),
        ]);

        return Inertia::render('Health/Reminders', ['reminders' => $reminders]);
    }

    public function store(StoreReminderRequest $request): RedirectResponse
    {
        $request->user()->reminders()->create($request->validated());

        return back()->with('status', 'reminder-added');
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->user_id === $request->user()->id, 404);

        $reminder->update(['last_completed_at' => now()->toDateString()]);

        return back()->with('status', 'reminder-marked-done');
    }
}
