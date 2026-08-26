<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subordinate to GymExercise (and transitively to Workout) — deliberately
 * NOT BelongsToUser (no user_id). Any endpoint that MUTATES a GymSet must
 * explicitly verify ownership via gymExercise->workout->user_id, since
 * there is no global scope to rely on (see Task 6's GymSetController).
 */
class GymSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'gym_exercise_id',
        'set_number',
        'planned_weight_kg',
        'planned_reps',
        'weight_kg',
        'reps',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'set_number' => 'integer',
            'planned_weight_kg' => 'decimal:2',
            'planned_reps' => 'integer',
            'weight_kg' => 'decimal:2',
            'reps' => 'integer',
        ];
    }

    public function gymExercise(): BelongsTo
    {
        return $this->belongsTo(GymExercise::class);
    }
}
