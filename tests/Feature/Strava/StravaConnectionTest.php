<?php

use App\Models\StravaConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('connect redirects to stravas authorization url and stores a csrf state in session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/integracje/strava/polacz');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://www.strava.com/oauth/authorize?');
    expect(session('strava_oauth_state'))->not->toBeNull();
});

test('callback with a mismatched state does not create a connection', function () {
    $user = User::factory()->create();
    $this->withSession(['strava_oauth_state' => 'expected-state']);

    $this->actingAs($user)->get('/integracje/strava/callback?code=abc&state=wrong-state');

    expect(StravaConnection::count())->toBe(0);
});

test('callback with a matching state exchanges the code and stores the connection', function () {
    $user = User::factory()->create();
    $this->withSession(['strava_oauth_state' => 'correct-state']);

    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'access-123',
            'refresh_token' => 'refresh-123',
            'expires_at' => now()->addHours(6)->timestamp,
            'athlete' => ['id' => 555],
        ]),
    ]);

    $this->actingAs($user)
        ->get('/integracje/strava/callback?code=abc&state=correct-state')
        ->assertRedirect(route('profile.edit'));

    expect(StravaConnection::where('user_id', $user->id)->first())
        ->strava_athlete_id->toBe(555)
        ->access_token->toBe('access-123');
});

test('a second callback for the same user updates the existing connection instead of duplicating it', function () {
    $user = User::factory()->create();
    StravaConnection::factory()->for($user)->create(['access_token' => 'stale-token']);
    $this->withSession(['strava_oauth_state' => 'state']);

    Http::fake([
        'https://www.strava.com/oauth/token' => Http::response([
            'access_token' => 'fresh-token',
            'refresh_token' => 'fresh-refresh',
            'expires_at' => now()->addHours(6)->timestamp,
            'athlete' => ['id' => 555],
        ]),
    ]);

    $this->actingAs($user)->get('/integracje/strava/callback?code=abc&state=state');

    expect(StravaConnection::where('user_id', $user->id)->count())->toBe(1)
        ->and(StravaConnection::where('user_id', $user->id)->first()->access_token)->toBe('fresh-token');
});

test('disconnect deletes the connection and calls stravas deauthorize endpoint', function () {
    $user = User::factory()->create();
    StravaConnection::factory()->for($user)->create();

    Http::fake([
        'https://www.strava.com/oauth/deauthorize' => Http::response([]),
    ]);

    $this->actingAs($user)
        ->delete('/integracje/strava')
        ->assertRedirect(route('profile.edit'));

    expect(StravaConnection::where('user_id', $user->id)->count())->toBe(0);
    Http::assertSent(fn ($request) => $request->url() === 'https://www.strava.com/oauth/deauthorize');
});

test('disconnect succeeds locally even if stravas deauthorize endpoint is unreachable', function () {
    $user = User::factory()->create();
    StravaConnection::factory()->for($user)->create();

    Http::fake([
        'https://www.strava.com/oauth/deauthorize' => Http::response([], 500),
    ]);

    $this->actingAs($user)->delete('/integracje/strava')->assertRedirect();

    expect(StravaConnection::where('user_id', $user->id)->count())->toBe(0);
});

test('a user never sees another users strava connection status', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    StravaConnection::factory()->for($userB)->create();

    $response = $this->actingAs($userA)->get('/profile');

    $response->assertInertia(fn ($page) => $page->where('stravaConnected', false));
});
