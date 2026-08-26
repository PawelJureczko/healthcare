<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shared exercise dictionary — deliberately NOT BelongsToUser (no user_id).
 * Visible to both of Centrum's users, same pattern as LabMarker (M1).
 */
class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'muscle_group',
        'lumbar_risk',
        'is_predefined',
    ];

    protected function casts(): array
    {
        return [
            'lumbar_risk' => 'boolean',
            'is_predefined' => 'boolean',
        ];
    }

    public function gymExercises(): HasMany
    {
        return $this->hasMany(GymExercise::class);
    }
}
