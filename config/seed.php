<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seeded Account Credentials
    |--------------------------------------------------------------------------
    |
    | Centrum has exactly two users. Their seeded credentials live in .env
    | and are read here (config files load before config:cache, so this is
    | the only safe place to call env() for these values). DatabaseSeeder
    | and its tests read via config('seed.*') so they keep working once
    | config:cache has run in production.
    |
    */

    'user_one' => [
        'name' => env('SEED_USER_ONE_NAME'),
        'email' => env('SEED_USER_ONE_EMAIL'),
        'password' => env('SEED_USER_ONE_PASSWORD'),
    ],

    'user_two' => [
        'name' => env('SEED_USER_TWO_NAME'),
        'email' => env('SEED_USER_TWO_EMAIL'),
        'password' => env('SEED_USER_TWO_PASSWORD'),
    ],

];
