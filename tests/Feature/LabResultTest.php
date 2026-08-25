<?php

use App\Models\LabMarker;
use App\Models\LabResult;
use App\Models\User;

test('a user can save a lab result with values for multiple markers', function () {
    $user = User::factory()->create();
    $cholesterol = LabMarker::factory()->create(['name' => 'Cholesterol całkowity']);
    $glucose = LabMarker::factory()->create(['name' => 'Glukoza']);

    $this->actingAs($user)
        ->post('/lab-results', [
            'performed_at' => '2025-01-15',
            'note' => 'Na czczo',
            'values' => [
                ['lab_marker_id' => $cholesterol->id, 'value' => 190.5],
                ['lab_marker_id' => $glucose->id, 'value' => 88],
            ],
        ])
        ->assertRedirect(route('lab-results.index'));

    $result = LabResult::where('user_id', $user->id)->first();
    expect($result->performed_at->toDateString())->toBe('2025-01-15')
        ->and($result->values()->count())->toBe(2);
});

test('a lab result can use a backdated date from over a year ago', function () {
    $user = User::factory()->create();
    $marker = LabMarker::factory()->create();

    $this->actingAs($user)->post('/lab-results', [
        'performed_at' => '2020-03-01',
        'values' => [['lab_marker_id' => $marker->id, 'value' => 100]],
    ])->assertRedirect();

    $this->assertDatabaseHas('lab_results', ['user_id' => $user->id, 'performed_at' => '2020-03-01']);
});

test('a user can add a custom lab marker to the shared dictionary', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/lab-markers', [
        'name' => 'Witamina D',
        'unit' => 'ng/ml',
        'norm_min' => 30,
        'norm_max' => 50,
    ])->assertRedirect();

    $this->assertDatabaseHas('lab_markers', ['name' => 'Witamina D', 'is_predefined' => false]);
});

test('a guest cannot save a lab result', function () {
    $marker = LabMarker::factory()->create();

    $this->post('/lab-results', [
        'performed_at' => '2026-01-01',
        'values' => [['lab_marker_id' => $marker->id, 'value' => 10]],
    ])->assertRedirect('/login');
});
