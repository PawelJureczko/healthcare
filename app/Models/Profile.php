<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'age',
        'height_cm',
        'weight_goal_kg',
        'injuries',
        'dietary_preferences',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'height_cm' => 'integer',
            'weight_goal_kg' => 'decimal:2',
        ];
    }
}
