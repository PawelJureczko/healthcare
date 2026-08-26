<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGymWorkoutRequest;
use App\Models\Exercise;
use App\Models\Workout;
use App\Services\ExerciseHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GymWorkoutController extends Controller
{
    public function create(Request $request): Response
    {
        $exercises = Exercise::orderBy('muscle_group')->orderBy('name')->get();
        $lastWeights = ExerciseHistory::lastWeights($request->user(), $exercises->pluck('id')->all());

        return Inertia::render('GymWorkouts/Create', [
            'exercises' => $exercises,
            'lastWeights' => $lastWeights,
        ]);
    }

    public function store(StoreGymWorkoutRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $workout = DB::transaction(function () use ($request, $data) {
            $workout = $request->user()->workouts()->create([
                'type' => 'gym',
                'date' => $data['date'],
                'status' => 'planned',
            ]);

            foreach ($data['exercises'] as $order => $exerciseData) {
                $gymExercise = $workout->gymExercises()->create([
                    'exercise_id' => $exerciseData['exercise_id'],
                    'order' => $order,
                ]);

                foreach ($exerciseData['sets'] as $setIndex => $setData) {
                    $gymExercise->gymSets()->create([
                        'set_number' => $setIndex + 1,
                        'planned_weight_kg' => $setData['planned_weight_kg'] ?? null,
                        'planned_reps' => $setData['planned_reps'],
                        'status' => 'pending',
                    ]);
                }
            }

            return $workout;
        });

        return redirect("/silownia/{$workout->id}");
    }
}
