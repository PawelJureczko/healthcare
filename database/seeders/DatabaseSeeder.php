<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the application's exactly-two user accounts.
     * Credentials come from config('seed.*') (backed by .env) — never
     * hardcode real passwords here. Missing config values fail loudly
     * instead of silently falling back to a weak default password.
     *
     * Uses firstOrCreate keyed on email so this is safe to run more than
     * once (e.g. `migrate --seed` on every deploy).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => config('seed.user_one.email')],
            [
                'name' => config('seed.user_one.name'),
                'password' => bcrypt(config('seed.user_one.password')),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => config('seed.user_two.email')],
            [
                'name' => config('seed.user_two.name'),
                'password' => bcrypt(config('seed.user_two.password')),
                'email_verified_at' => now(),
            ]
        );
    }
}
