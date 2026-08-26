<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSportSessionRequest;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SportSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = Workout::forUser($request->user())
            ->where('type', 'sport')
            ->with('sportSession')
            ->orderBy('date')
            ->get()
            ->map(fn ($workout) => [
                'id' => $workout->id,
                'date' => $workout->date->format('Y-m-d'),
                'sport_subtype' => $workout->sport_subtype,
                'duration_min' => round($workout->sportSession->duration_s / 60, 1),
                'intensity' => $workout->sportSession->intensity,
                'source' => $workout->sportSession->strava_activity_id ? 'strava' : 'manual',
            ]);

        return Inertia::render('Sports/Index', ['sessions' => $sessions]);
    }

    public function create(): Response
    {
        return Inertia::render('Sports/Create');
    }

    public function store(StoreSportSessionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $durationS = (int) round($data['duration_min'] * 60);

        DB::transaction(function () use ($request, $data, $durationS) {
            $workout = $request->user()->workouts()->create([
                'type' => 'sport',
                'sport_subtype' => $data['sport_subtype'],
                'date' => $data['date'],
                'status' => 'completed',
                'comment' => $data['comment'] ?? null,
            ]);

            $workout->sportSession()->create([
                'duration_s' => $durationS,
                'intensity' => $data['intensity'],
            ]);
        });

        return redirect()->route('sport-sessions.index')->with('status', 'sport-session-saved');
    }
}
