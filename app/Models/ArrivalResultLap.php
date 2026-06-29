<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'arrival_result_id',
    'lap_number',
    'lap_time_ms',
    'timestamp_ms',
    'position_on_lap',
    'is_manual',
])]
class ArrivalResultLap extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arrival_result_id' => 'integer',
            'lap_number' => 'integer',
            'lap_time_ms' => 'integer',
            'timestamp_ms' => 'integer',
            'position_on_lap' => 'integer',
            'is_manual' => 'boolean',
        ];
    }

    public function arrivalResult(): BelongsTo
    {
        return $this->belongsTo(ArrivalResult::class);
    }
}
