<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExerciseRequest;
use App\Models\Exercise;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Exercises/Index', [
            'exercises' => Exercise::orderBy('muscle_group')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreExerciseRequest $request): RedirectResponse
    {
        Exercise::create([
            ...$request->validated(),
            'is_predefined' => false,
        ]);

        return back()->with('status', 'exercise-added');
    }
}
