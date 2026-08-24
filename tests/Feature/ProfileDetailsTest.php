<?php

use App\Models\User;

test('a user can update their health profile details', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile/details', [
            'age' => 35,
            'height_cm' => 186,
            'weight_goal_kg' => 80,
            'injuries' => 'Kontuzja lędźwi przy martwym ciągu',
            'dietary_preferences' => 'Bez laktozy',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('profiles', [
        'user_id' => $user->id,
        'age' => 35,
        'height_cm' => 186,
        'injuries' => 'Kontuzja lędźwi przy martwym ciągu',
    ]);
});

test('a guest cannot update profile details', function () {
    $this->patch('/profile/details', ['age' => 35])
        ->assertRedirect('/login');
});
