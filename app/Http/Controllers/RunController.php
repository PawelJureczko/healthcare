<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRunRequest;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RunController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $runs = Workout::forUser($user)
            ->where('type', 'run')
            ->with('run')
            ->orderBy('date')
            ->get()
            ->map(fn ($workout) => [
                'id' => $workout->id,
                'date' => $workout->date->format('Y-m-d'),
                'distance_km' => round($workout->run->distance_m / 1000, 2),
                'duration_min' => round($workout->run->duration_s / 60, 1),
                'avg_pace_s_per_km' => $workout->run->avg_pace_s_per_km,
                'avg_heart_rate' => $workout->run->avg_heart_rate,
                'source' => $workout->run->strava_activity_id ? 'strava' : 'manual',
            ]);

        return Inertia::render('Runs/Index', [
            'runs' => $runs,
            'activeGoal' => null,
            'stravaConnected' => (bool) $user->stravaConnection,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Runs/Create');
    }

    public function store(StoreRunRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $distanceM = (int) round($data['distance_km'] * 1000);
        $durationS = (int) round($data['duration_min'] * 60);

        DB::transaction(function () use ($request, $data, $distanceM, $durationS) {
            $workout = $request->user()->workouts()->create([
                'type' => 'run',
                'date' => $data['date'],
                'status' => 'completed',
                'comment' => $data['comment'] ?? null,
                'wellbeing_rating' => $data['wellbeing_rating'] ?? null,
            ]);

            $workout->run()->create([
                'distance_m' => $distanceM,
                'duration_s' => $durationS,
                'avg_pace_s_per_km' => $distanceM > 0 ? (int) round($durationS / ($distanceM / 1000)) : null,
                'avg_heart_rate' => $data['avg_heart_rate'] ?? null,
            ]);
        });

        return redirect()->route('runs.index')->with('status', 'run-saved');
    }
}
