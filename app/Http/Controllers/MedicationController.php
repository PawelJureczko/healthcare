<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicationRequest;
use App\Http\Requests\UpdateMedicationRequest;
use App\Models\Medication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedicationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Health/Medications', [
            'medications' => $request->user()->medications()->orderByDesc('started_at')->get(),
        ]);
    }

    public function store(StoreMedicationRequest $request): RedirectResponse
    {
        $request->user()->medications()->create($request->validated());

        return back()->with('status', 'medication-added');
    }

    public function update(UpdateMedicationRequest $request, Medication $medication): RedirectResponse
    {
        $medication->update($request->validated());

        return back()->with('status', 'medication-updated');
    }
}
