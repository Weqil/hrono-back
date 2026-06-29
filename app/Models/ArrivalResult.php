<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'arrival_id',
    'server_race_id',
    'place',
    'total_laps',
    'total_time_ms',
    'best_lap_time_ms',
    'user_id',
    'name',
    'surname',
    'patronymic',
    'start_number',
    'tag_id',
    'grade',
    'command',
])]
class ArrivalResult extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'arrival_id' => 'integer',
            'server_race_id' => 'integer',
            'place' => 'integer',
            'total_laps' => 'integer',
            'total_time_ms' => 'integer',
            'best_lap_time_ms' => 'integer',
            'user_id' => 'integer',
            'start_number' => 'integer',
        ];
    }

    public function arrival(): BelongsTo
    {
        return $this->belongsTo(Arrival::class);
    }

    public function laps(): HasMany
    {
        return $this->hasMany(ArrivalResultLap::class);
    }
}
