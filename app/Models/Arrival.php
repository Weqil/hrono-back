<?php

namespace App\Models;

use App\Application\Arrival\Enums\ArrivalKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'finished',
    'round_min_time',
    'time',
    'arrival_grades',
    'moto_race_id',
    'arrival_type_id',
    'local_arrival_id',
    'finished_at',
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
            'arrival_type_id' => 'integer',
            'local_arrival_id' => 'integer',
            'finished_at' => 'datetime',
            'moto_stream_opened_at' => 'datetime',
            'moto_stream_closed_at' => 'datetime',
            'moto_stream_id' => 'string',
            'stream_auto_close_at' => 'datetime',
            'moto_stream_bearer' => 'string',
            'last_live_results_at' => 'datetime',
        ];
    }

    public function arrivalType(): BelongsTo
    {
        return $this->belongsTo(ArrivalType::class);
    }

    public function kind(): ?ArrivalKind
    {
        return $this->arrivalType?->slug;
    }

    public function isQualification(): bool
    {
        return $this->kind() === ArrivalKind::Qualification;
    }

    public function isRegular(): bool
    {
        return $this->kind() === ArrivalKind::Regular;
    }

    public function results(): HasMany
    {
        return $this->hasMany(ArrivalResult::class);
    }

    public function hasFinalResults(): bool
    {
        return $this->finished_at !== null && $this->results()->exists();
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

    /**
     * Whether this arrival holds the current open Moto stream for its race.
     * Moto stream is per race — only the latest open arrival is active.
     */
    public function isCurrentMotoStream(): bool
    {
        if (! $this->canCloseMotoStream()) {
            return false;
        }

        $activeArrivalId = static::query()
            ->where('moto_race_id', $this->moto_race_id)
            ->whereNotNull('moto_stream_opened_at')
            ->whereNull('moto_stream_closed_at')
            ->orderByDesc('moto_stream_opened_at')
            ->orderByDesc('id')
            ->value('id');

        return $activeArrivalId !== null
            && (int) $activeArrivalId === (int) $this->getKey();
    }

    public function isStreamAutoCloseDue(?\DateTimeInterface $now = null): bool
    {
        if (! $this->canCloseMotoStream() || $this->moto_stream_opened_at === null) {
            return false;
        }

        $now = $now ?? now();
        $dueAt = $this->stream_auto_close_at
            ?? \App\Support\ArrivalStreamAutoClose::dueAt($this, $this->moto_stream_opened_at);

        if ($dueAt === null) {
            return false;
        }

        return $dueAt->lessThanOrEqualTo($now);
    }

    /**
     * Live results may be forwarded only for the active arrival.
     * Blocks closed / finished / superseded arrivals so delayed checkpoint
     * packets cannot overwrite another heat on the same moto race.
     * Past stream_auto_close_at still forwards: activity extends the idle deadline.
     */
    public function canForwardLiveResultsToMoto(?\DateTimeInterface $now = null): bool
    {
        if ($this->moto_stream_closed_at !== null) {
            return false;
        }

        if ($this->hasFinalResults()) {
            return false;
        }

        // Not opened yet: allow only when no other heat still has an open flag
        // (open path may start the Mototrek stream in the same request).
        if ($this->moto_stream_opened_at === null) {
            return ! static::query()
                ->where('moto_race_id', $this->moto_race_id)
                ->whereKeyNot($this->getKey())
                ->whereNotNull('moto_stream_opened_at')
                ->whereNull('moto_stream_closed_at')
                ->exists();
        }

        // Already opened: the latest open arrival may forward even if older rows
        // failed to clear their open flags (previously blocked all live updates).
        return $this->isCurrentMotoStream();
    }
}
