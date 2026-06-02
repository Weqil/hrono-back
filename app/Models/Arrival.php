<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'finished',
    'round_min_time',
    'time',
    'arrival_grades',
    'moto_race_id',
])]
class Arrival extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'finished' => 'boolean',
            'round_min_time' => 'integer',
            'arrival_grades' => 'array',
            'moto_race_id' => 'integer',
            'moto_stream_opened_at' => 'datetime',
            'moto_stream_closed_at' => 'datetime',
        ];
    }

    public function isMotoStreamOpen(): bool
    {
        return $this->moto_stream_opened_at !== null
            && $this->moto_stream_closed_at === null;
    }

    public function canOpenMotoStream(): bool
    {
        return $this->moto_stream_opened_at === null
            && $this->moto_stream_closed_at === null;
    }

    public function canCloseMotoStream(): bool
    {
        return $this->moto_stream_opened_at !== null
            && $this->moto_stream_closed_at === null;
    }
}
