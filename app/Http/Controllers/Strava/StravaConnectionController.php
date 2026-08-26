<?php

namespace App\Http\Controllers\Strava;

use App\Http\Controllers\Controller;
use App\Services\Strava\StravaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class StravaConnectionController extends Controller
{
    public function redirect(Request $request, StravaClient $client): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('strava_oauth_state', $state);

        return redirect($client->authorizationUrl($state));
    }

    public function callback(Request $request, StravaClient $client): RedirectResponse
    {
        $expectedState = $request->session()->pull('strava_oauth_state');

        if (! $request->has('code') || ! is_string($expectedState) || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect()->route('profile.edit')->with('status', 'strava-connect-failed');
        }

        $token = $client->exchangeCodeForToken($request->get('code'));

        $request->user()->stravaConnection()->updateOrCreate([], [
            'strava_athlete_id' => $token['athlete']['id'] ?? null,
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
            'token_expires_at' => Carbon::createFromTimestamp($token['expires_at']),
        ]);

        return redirect()->route('profile.edit')->with('status', 'strava-connected');
    }

    public function destroy(Request $request, StravaClient $client): RedirectResponse
    {
        $connection = $request->user()->stravaConnection;

        if ($connection) {
            try {
                $client->deauthorize($connection);
            } catch (\Throwable) {
                // Best-effort remote revoke — local disconnect proceeds regardless.
            }

            $connection->delete();
        }

        return redirect()->route('profile.edit')->with('status', 'strava-disconnected');
    }
}
