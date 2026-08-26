<?php

use App\Models\Exercise;
use App\Models\User;

test('the exercise dictionary is visible to any authenticated user', function () {
    $user = User::factory()->create();
    Exercise::factory()->create(['name' => 'Test A', 'muscle_group' => 'nogi']);

    $response = $this->actingAs($user)->get('/cwiczenia');

    $response->assertInertia(fn ($page) => $page->component('Exercises/Index')->has('exercises', 1));
});

test('a user can add a custom exercise', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/cwiczenia', [
        'name' => 'Wykrok Bułgarski',
        'muscle_group' => 'nogi',
        'lumbar_risk' => false,
    ])->assertRedirect();

    $this->assertDatabaseHas('exercises', [
        'name' => 'Wykrok Bułgarski',
        'muscle_group' => 'nogi',
        'is_predefined' => false,
    ]);
});

test('exercise name must be unique', function () {
    $user = User::factory()->create();
    Exercise::factory()->create(['name' => 'Duplikat']);

    $this->actingAs($user)
        ->post('/cwiczenia', ['name' => 'Duplikat', 'muscle_group' => 'nogi', 'lumbar_risk' => false])
        ->assertSessionHasErrors('name');
});

test('a guest cannot add an exercise', function () {
    $this->post('/cwiczenia', ['name' => 'X', 'muscle_group' => 'nogi', 'lumbar_risk' => false])
        ->assertRedirect('/login');
});
