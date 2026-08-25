<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabResult extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'performed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'date',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(LabValue::class);
    }
}
