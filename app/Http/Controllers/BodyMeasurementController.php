<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBodyMeasurementRequest;
use App\Models\BodyMeasurement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BodyMeasurementController extends Controller
{
    public function index(Request $request): Response
    {
        $measurements = BodyMeasurement::forUser($request->user())
            ->orderBy('date')
            ->get(['date', 'weight_kg', 'waist_cm']);

        return Inertia::render('Body/Index', [
            'measurements' => $measurements,
            'weightGoalKg' => $request->user()->profile?->weight_goal_kg,
        ]);
    }

    public function store(StoreBodyMeasurementRequest $request): RedirectResponse
    {
        $request->user()->bodyMeasurements()->updateOrCreate(
            ['date' => $request->validated('date')],
            $request->validated()
        );

        return back()->with('status', 'body-measurement-saved');
    }
}
