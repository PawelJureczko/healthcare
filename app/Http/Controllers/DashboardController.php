<?php

namespace App\Http\Controllers;

use App\Models\BloodPressureReading;
use App\Models\Workout;
use App\Services\ReminderStatus;
use App\Services\TrainingGoalProgress;
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
            ->sortBy(fn ($r) => $r['days_until_due'] ?? -INF)
            ->first();

        $currentWeight = WeightTrend::sevenDayAverage($user);
        $weightGoal = $user->profile?->weight_goal_kg;

        $activeGoal = $user->trainingGoals()->where('type', 'run_distance')->where('status', 'active')->latest('target_date')->first();

        $lastCompletedGymWorkout = Workout::forUser($user)
            ->where('type', 'gym')
            ->where('status', 'completed')
            ->whereNotNull('back_pain_rating')
            ->latest('date')
            ->first();

        $hasPlannedGymWorkout = Workout::forUser($user)
            ->where('type', 'gym')
            ->where('status', 'planned')
            ->exists();

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
            'running' => [
                'activeGoal' => $activeGoal ? [
                    'target_distance_km' => round($activeGoal->target_distance_m / 1000, 2),
                    'target_date' => $activeGoal->target_date->format('Y-m-d'),
                    'progressPercent' => TrainingGoalProgress::percent($activeGoal),
                ] : null,
                'stravaConnected' => (bool) $user->stravaConnection,
            ],
            'gym' => [
                'lastBackPainRating' => $lastCompletedGymWorkout?->back_pain_rating,
                'hasPlannedWorkout' => $hasPlannedGymWorkout,
            ],
        ]);
    }
}
