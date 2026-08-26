<?php

namespace App\Http\Controllers\Strava;

use App\Http\Controllers\Controller;
use App\Services\Strava\StravaSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StravaSyncController extends Controller
{
    public function __invoke(Request $request, StravaSyncService $syncService): RedirectResponse
    {
        $connection = $request->user()->stravaConnection;

        if (! $connection) {
            return back()->with('status', 'strava-not-connected');
        }

        $imported = $syncService->sync($connection);

        return back()->with('status', "strava-synced:{$imported}");
    }
}
