<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'interval_days',
        'last_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_completed_at' => 'date',
        ];
    }
}
