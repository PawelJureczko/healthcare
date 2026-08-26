<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseProgressionController extends Controller
{
    public function show(Request $request, Exercise $exercise): Response
    {
        $sessions = Workout::forUser($request->user())
            ->where('type', 'gym')
            ->whereHas('gymExercises', fn ($query) => $query->where('exercise_id', $exercise->id))
            ->with(['gymExercises' => fn ($query) => $query->where('exercise_id', $exercise->id)->with('gymSets')])
            ->orderBy('date')
            ->get()
            ->map(function ($workout) {
                $maxWeight = $workout->gymExercises
                    ->flatMap(fn ($gymExercise) => $gymExercise->gymSets)
                    ->where('status', 'done')
                    ->max('weight_kg');

                return [
                    'date' => $workout->date->format('Y-m-d'),
                    'maxWeightKg' => $maxWeight !== null ? (float) $maxWeight : null,
                ];
            })
            ->filter(fn ($session) => $session['maxWeightKg'] !== null)
            ->values();

        return Inertia::render('Exercises/Progression', [
            'exercise' => ['id' => $exercise->id, 'name' => $exercise->name],
            'sessions' => $sessions,
        ]);
    }
}
