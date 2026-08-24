<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the application's exactly-two user accounts.
     * Credentials come from .env — never hardcode real passwords here.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => env('SEED_USER_ONE_NAME', 'User One'),
            'email' => env('SEED_USER_ONE_EMAIL', 'user1@centrum.local'),
            'password' => bcrypt(env('SEED_USER_ONE_PASSWORD', 'password')),
        ]);

        User::factory()->create([
            'name' => env('SEED_USER_TWO_NAME', 'User Two'),
            'email' => env('SEED_USER_TWO_EMAIL', 'user2@centrum.local'),
            'password' => bcrypt(env('SEED_USER_TWO_PASSWORD', 'password')),
        ]);
    }
}
