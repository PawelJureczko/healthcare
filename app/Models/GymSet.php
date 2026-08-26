<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subordinate to GymExercise (and transitively to Workout) — deliberately
 * NOT BelongsToUser (no user_id). Any endpoint that MUTATES a GymSet must
 * explicitly verify ownership, and must NOT do so via a plain
 * gymExercise->workout->user_id relation access: Workout's BelongsToUser
 * global scope filters that relation to the currently authenticated user,
 * so for a non-owner it resolves to null and ->user_id on null throws a
 * 500 instead of yielding a clean 404. Bypass the scope explicitly instead,
 * as GymSetController::update() does:
 *
 *     $ownerId = $gymSet->gymExercise->workout()
 *         ->withoutGlobalScope(UserOwnedScope::class)
 *         ->value('user_id');
 *
 *     abort_unless($ownerId === $request->user()->id, 404);
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
