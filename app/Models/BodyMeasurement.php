<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BodyMeasurement extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'date',
        'weight_kg',
        'waist_cm',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'weight_kg' => 'decimal:2',
            'waist_cm' => 'decimal:2',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
