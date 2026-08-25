<?php

namespace App\Http\Controllers;

use App\Models\BloodPressureReading;
use App\Services\ReminderStatus;
use App\Services\WeightTrend;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $latestBloodPressure = BloodPressureReading::forUser($user)
            ->latest('measured_at')
            ->first();

        $nextReminder = $user->reminders()
            ->get()
            ->map(fn ($reminder) => [
                'type' => $reminder->type,
                'days_until_due' => ReminderStatus::daysUntilDue($reminder),
            ])
            ->filter(fn ($r) => $r['days_until_due'] !== null)
            ->sortBy('days_until_due')
            ->first();

        $currentWeight = WeightTrend::sevenDayAverage($user);
        $weightGoal = $user->profile?->weight_goal_kg;

        return Inertia::render('Dashboard', [
            'weight' => [
                'sevenDayAverage' => $currentWeight,
                'weeklyTrend' => WeightTrend::weeklyTrend($user),
                'distanceToGoal' => ($currentWeight !== null && $weightGoal !== null)
                    ? round($currentWeight - (float) $weightGoal, 1)
                    : null,
            ],
            'health' => [
                'lastBloodPressure' => $latestBloodPressure
                    ? "{$latestBloodPressure->systolic}/{$latestBloodPressure->diastolic}"
                    : null,
                'nextReminder' => $nextReminder,
            ],
        ]);
    }
}
