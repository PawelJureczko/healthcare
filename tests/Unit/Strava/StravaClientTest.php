<?php

use App\Models\StravaConnection;
use App\Services\Strava\StravaClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.strava.client_id' => '12345']);
    config(['services.strava.client_secret' => 'secret']);
    config(['services.strava.redirect' => 'http://localhost/integracje/strava/callback']);
});

test('authorization url contains client id, redirect, scope and state', function () {
    $url = (new StravaClient)->authorizationUrl('the-state-value');

    expect($url)->toStartWith('https://www.strava.com/oauth/authorize?')
        ->and($url)->toContain('client_id=12345')
        ->and($url)->toContain('state=the-state-value')
        ->and($url)->toContain('scope=read%2Cactivity%3Aread_all');
});

test('exchangeCodeForToken posts to the strava token endpoint and returns the json body', function () {
    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'access-123',
            'refresh_token' => 'refresh-123',
            'expires_at' => now()->addHours(6)->timestamp,
            'athlete' => ['id' => 999],
        ]),
    ]);

    $result = (new StravaClient)->exchangeCodeForToken('the-code');

    expect($result['access_token'])->toBe('access-123');
    Http::assertSent(fn ($request) => $request->url() === 'https://www.strava.com/oauth/token'
        && $request['code'] === 'the-code'
        && $request['grant_type'] === 'authorization_code');
});

test('ensureFreshToken does nothing when the token is not yet expired', function () {
    Http::fake();
    $connection = StravaConnection::factory()->make(['token_expires_at' => now()->addHour()]);

    (new StravaClient)->ensureFreshToken($connection);

    Http::assertNothingSent();
});

test('ensureFreshToken refreshes and persists a new token when expired', function () {
    $connection = StravaConnection::factory()->create([
        'token_expires_at' => now()->subMinute(),
        'access_token' => 'old-access',
        'refresh_token' => 'old-refresh',
    ]);

    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'new-access',
            'refresh_token' => 'new-refresh',
            'expires_at' => now()->addHours(6)->timestamp,
        ]),
    ]);

    (new StravaClient)->ensureFreshToken($connection);

    expect($connection->fresh()->access_token)->toBe('new-access')
        ->and($connection->fresh()->refresh_token)->toBe('new-refresh');
});

test('fetchActivitiesPage calls the activities endpoint with page, per_page and after', function () {
    $connection = StravaConnection::factory()->create(['access_token' => 'access-123']);

    Http::fake([
        'https://www.strava.com/api/v3/athlete/activities*' => Http::response([]),
    ]);

    (new StravaClient)->fetchActivitiesPage($connection, 1700000000, 2, 100);

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://www.strava.com/api/v3/athlete/activities')
        && $request['page'] === 2
        && $request['per_page'] === 100
        && $request['after'] === 1700000000
        && $request->hasHeader('Authorization', 'Bearer access-123'));
});

test('deauthorize posts the access token to the deauthorize endpoint', function () {
    $connection = StravaConnection::factory()->create(['access_token' => 'access-123']);

    Http::fake([
        'https://www.strava.com/oauth/deauthorize' => Http::response([]),
    ]);

    (new StravaClient)->deauthorize($connection);

    Http::assertSent(fn ($request) => $request->url() === 'https://www.strava.com/oauth/deauthorize'
        && $request['access_token'] === 'access-123');
});
