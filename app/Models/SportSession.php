<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subordinate 1:1 to Workout — same isolation model as Run (see its
 * docblock): no user_id, no BelongsToUser, scoped transitively via
 * workout_id.
 */
class SportSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'duration_s',
        'kcal',
        'avg_heart_rate',
        'intensity',
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
