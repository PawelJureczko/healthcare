<?php

namespace App\Services\Strava;

use App\Models\StravaConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class StravaClient
{
    private const AUTHORIZE_URL = 'https://www.strava.com/oauth/authorize';
    private const TOKEN_URL = 'https://www.strava.com/oauth/token';
    private const DEAUTHORIZE_URL = 'https://www.strava.com/oauth/deauthorize';
    private const ACTIVITIES_URL = 'https://www.strava.com/api/v3/athlete/activities';

    public function authorizationUrl(string $state): string
    {
        return self::AUTHORIZE_URL.'?'.http_build_query([
            'client_id' => config('services.strava.client_id'),
            'redirect_uri' => config('services.strava.redirect'),
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'scope' => 'read,activity:read_all',
            'state' => $state,
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        return Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ])->throw()->json();
    }

    public function ensureFreshToken(StravaConnection $connection): StravaConnection
    {
        if ($connection->token_expires_at->isFuture()) {
            return $connection;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.strava.client_id'),
            'client_secret' => config('services.strava.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ])->throw()->json();

        $connection->update([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'token_expires_at' => Carbon::createFromTimestamp($response['expires_at']),
        ]);

        return $connection;
    }

    public function fetchActivitiesPage(StravaConnection $connection, ?int $after, int $page, int $perPage = 100): array
    {
        $query = ['page' => $page, 'per_page' => $perPage];
        if ($after !== null) {
            $query['after'] = $after;
        }

        return Http::withToken($connection->access_token)
            ->get(self::ACTIVITIES_URL, $query)
            ->throw()
            ->json();
    }

    public function deauthorize(StravaConnection $connection): void
    {
        Http::asForm()->post(self::DEAUTHORIZE_URL, [
            'access_token' => $connection->access_token,
        ]);
    }
}
