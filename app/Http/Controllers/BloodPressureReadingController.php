<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodPressureReadingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BloodPressureReadingController extends Controller
{
    public function index(Request $request): Response
    {
        $readings = $request->user()->bloodPressureReadings()
            ->orderBy('measured_at')
            ->get(['measured_at', 'systolic', 'diastolic', 'resting_pulse']);

        return Inertia::render('Health/BloodPressure', [
            'readings' => $readings,
        ]);
    }

    public function store(StoreBloodPressureReadingRequest $request): RedirectResponse
    {
        $request->user()->bloodPressureReadings()->create($request->validated());

        return back()->with('status', 'blood-pressure-saved');
    }
}
