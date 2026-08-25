<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabMarker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'norm_min',
        'norm_max',
        'is_predefined',
    ];

    protected function casts(): array
    {
        return [
            'norm_min' => 'decimal:2',
            'norm_max' => 'decimal:2',
            'is_predefined' => 'boolean',
        ];
    }
}
