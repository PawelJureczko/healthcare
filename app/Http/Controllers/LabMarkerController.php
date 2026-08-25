<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabMarkerRequest;
use App\Models\LabMarker;
use Illuminate\Http\RedirectResponse;

class LabMarkerController extends Controller
{
    public function store(StoreLabMarkerRequest $request): RedirectResponse
    {
        LabMarker::create([
            ...$request->validated(),
            'is_predefined' => false,
        ]);

        return back()->with('status', 'lab-marker-added');
    }
}
