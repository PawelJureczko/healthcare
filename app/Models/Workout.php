<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Workout extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'sport_subtype',
        'date',
        'status',
        'comment',
        'wellbeing_rating',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    public function run(): HasOne
    {
        return $this->hasOne(Run::class);
    }

    public function sportSession(): HasOne
    {
        return $this->hasOne(SportSession::class);
    }
}
