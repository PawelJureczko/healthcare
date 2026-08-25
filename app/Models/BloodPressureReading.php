<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodPressureReading extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'measured_at',
        'systolic',
        'diastolic',
        'resting_pulse',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
        ];
    }
}
