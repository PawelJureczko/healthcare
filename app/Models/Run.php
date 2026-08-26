<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subordinate 1:1 to Workout — deliberately NOT BelongsToUser (no user_id
 * column). Isolation is inherited transitively through workout_id, exactly
 * like LabValue/lab_result_id in M1. Always query through
 * Workout::forUser($user)->with('run'), never Run::all() directly in a
 * user-facing path.
 */
class Run extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'distance_m',
        'duration_s',
        'avg_pace_s_per_km',
        'avg_heart_rate',
        'max_heart_rate',
        'kcal',
        'strava_activity_id',
        'strava_raw',
    ];

    protected function casts(): array
    {
        return [
            'strava_raw' => 'array',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }
}
