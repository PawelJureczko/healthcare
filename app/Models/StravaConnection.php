<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StravaConnection extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'strava_athlete_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
