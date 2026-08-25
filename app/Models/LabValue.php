<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_result_id',
        'lab_marker_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public function labResult(): BelongsTo
    {
        return $this->belongsTo(LabResult::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(LabMarker::class, 'lab_marker_id');
    }
}
