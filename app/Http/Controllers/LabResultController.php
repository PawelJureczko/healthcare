<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabResultRequest;
use App\Models\LabMarker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LabResultController extends Controller
{
    public function index(Request $request): Response
    {
        $results = $request->user()->labResults()
            ->with('values.marker')
            ->orderBy('performed_at')
            ->get();

        return Inertia::render('Health/Labs/Index', [
            'results' => $results,
            'markers' => LabMarker::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Health/Labs/Create', [
            'markers' => LabMarker::orderBy('name')->get(),
        ]);
    }

    public function store(StoreLabResultRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $result = $request->user()->labResults()->create([
                'performed_at' => $request->validated('performed_at'),
                'note' => $request->validated('note'),
            ]);

            $result->values()->createMany($request->validated('values'));
        });

        return redirect()->route('lab-results.index')->with('status', 'lab-result-saved');
    }
}
