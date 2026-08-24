<?php

use App\Models\Profile;
use App\Models\User;

test('a user never sees another users scoped data', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Profile::factory()->for($userA)->create();
    Profile::factory()->for($userB)->create();

    $this->actingAs($userA);

    expect(Profile::count())->toBe(1)
        ->and(Profile::first()->user_id)->toBe($userA->id);
});

test('a user cannot fetch another users record by id', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $profileB = Profile::factory()->for($userB)->create();

    $this->actingAs($userA);

    expect(Profile::find($profileB->id))->toBeNull();
});

test('creating a scoped record auto-assigns the authenticated users id', function () {
    $userA = User::factory()->create();
    $this->actingAs($userA);

    $profile = Profile::factory()->make(['user_id' => null]);
    $profile->save();

    expect($profile->user_id)->toBe($userA->id);
});
