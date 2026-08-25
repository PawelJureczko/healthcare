<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBodyMeasurementRequest;
use App\Models\BodyMeasurement;
use App\Services\WeightTrend;
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
            'sevenDayAverages' => WeightTrend::sevenDayAverageSeries($measurements->toArray()),
            'weightGoalKg' => $request->user()->profile?->weight_goal_kg,
        ]);
    }

    public function store(StoreBodyMeasurementRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (($data['waist_cm'] ?? null) === null) {
            unset($data['waist_cm']);
        }

        $request->user()->bodyMeasurements()->updateOrCreate(
            ['date' => $data['date']],
            $data
        );

        return back()->with('status', 'body-measurement-saved');
    }
}
