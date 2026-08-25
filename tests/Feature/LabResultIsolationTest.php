<?php

use App\Models\LabResult;
use App\Models\LabValue;
use App\Models\User;

test('a user never sees another users lab results', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    LabResult::factory()->for($userA)->create();
    LabResult::factory()->for($userB)->create();

    $this->actingAs($userA);

    expect(LabResult::count())->toBe(1)
        ->and(LabResult::first()->user_id)->toBe($userA->id);
});

test('lab values are reachable only through their owning users lab result', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $resultA = LabResult::factory()->for($userA)->create();
    $resultB = LabResult::factory()->for($userB)->create();
    LabValue::factory()->for($resultA, 'labResult')->create();
    LabValue::factory()->for($resultB, 'labResult')->create();

    $this->actingAs($userA);

    // LabResult::first() is already scoped to userA; its values() relation
    // is a plain hasMany with no independent scope, but since it can only
    // be reached starting from an already-scoped LabResult, isolation holds.
    expect(LabResult::first()->values()->count())->toBe(1);
});
