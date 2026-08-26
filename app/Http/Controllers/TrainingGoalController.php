<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingGoalRequest;
use Illuminate\Http\RedirectResponse;

class TrainingGoalController extends Controller
{
    public function store(StoreTrainingGoalRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->trainingGoals()
            ->where('type', 'run_distance')
            ->where('status', 'active')
            ->update(['status' => 'abandoned']);

        $request->user()->trainingGoals()->create([
            'type' => 'run_distance',
            'target_distance_m' => (int) round($data['target_distance_km'] * 1000),
            'target_date' => $data['target_date'],
            'target_time_s' => isset($data['target_time_min']) ? (int) round($data['target_time_min'] * 60) : null,
            'status' => 'active',
        ]);

        return redirect()->route('runs.index')->with('status', 'goal-saved');
    }
}
