<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subordinate to Workout — deliberately NOT BelongsToUser (no user_id).
 * Isolation is inherited transitively through workout_id, exactly like
 * Run/SportSession in M2. Always query through Workout::forUser($user),
 * never GymExercise:: directly in a user-facing read path.
 */
class GymExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'exercise_id',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function gymSets(): HasMany
    {
        return $this->hasMany(GymSet::class);
    }
}
