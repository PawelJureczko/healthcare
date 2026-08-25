<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'dose',
        'started_at',
        'stopped_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'stopped_at' => 'date',
        ];
    }

    public function isActive(): bool
    {
        return $this->stopped_at === null;
    }
}
