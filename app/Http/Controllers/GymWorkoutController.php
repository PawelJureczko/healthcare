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
    public function index(Request $request): Response
    {
        $workouts = Workout::forUser($request->user())
            ->where('type', 'gym')
            ->orderByDesc('date')
            ->get(['id', 'date', 'status', 'back_pain_rating', 'wellbeing_rating'])
            ->map(fn ($workout) => [
                'id' => $workout->id,
                'date' => $workout->date->format('Y-m-d'),
                'status' => $workout->status,
                'back_pain_rating' => $workout->back_pain_rating,
                'wellbeing_rating' => $workout->wellbeing_rating,
            ]);

        return Inertia::render('GymWorkouts/Index', ['workouts' => $workouts]);
    }

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

    public function show(Request $request, Workout $workout): Response
    {
        abort_unless($workout->user_id === $request->user()->id, 404);

        $workout->load('gymExercises.exercise', 'gymExercises.gymSets');
        $exerciseIds = $workout->gymExercises->pluck('exercise_id')->all();
        $lastWeights = ExerciseHistory::lastWeights($request->user(), $exerciseIds);

        return Inertia::render('GymWorkouts/Show', [
            'workout' => [
                'id' => $workout->id,
                'date' => $workout->date->format('Y-m-d'),
                'status' => $workout->status,
                'gymExercises' => $workout->gymExercises->map(fn ($gymExercise) => [
                    'id' => $gymExercise->id,
                    'exercise' => [
                        'id' => $gymExercise->exercise->id,
                        'name' => $gymExercise->exercise->name,
                        'lumbar_risk' => $gymExercise->exercise->lumbar_risk,
                    ],
                    'lastWeight' => $lastWeights[$gymExercise->exercise_id] ?? null,
                    'gymSets' => $gymExercise->gymSets->map(fn ($set) => [
                        'id' => $set->id,
                        'set_number' => $set->set_number,
                        'planned_weight_kg' => $set->planned_weight_kg !== null ? (float) $set->planned_weight_kg : null,
                        'planned_reps' => $set->planned_reps,
                        'weight_kg' => $set->weight_kg !== null ? (float) $set->weight_kg : null,
                        'reps' => $set->reps,
                        'status' => $set->status,
                    ]),
                ]),
            ],
        ]);
    }
}
