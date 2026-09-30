<?php

namespace App\Jobs;

use App\Application\Arrival\Actions\AutoCloseArrivalStreamAction;
use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Models\Arrival;
use App\Support\ArrivalStreamAutoClose;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class AutoCloseArrivalStreamJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public readonly int $arrivalId,
    ) {}

    public function handle(AutoCloseArrivalStreamAction $action): void
    {
        $outcome = $action->execute($this->arrivalId);

        Log::channel('info')->info('arrivals.stream.auto_close_job', [
            'arrival_id' => $this->arrivalId,
            'outcome' => $outcome->name,
        ]);

        // Reschedule only on real async queues. Sync ignores delay and would loop forever.
        if (config('queue.default') === 'sync') {
            return;
        }

        if ($outcome === CloseArrivalStreamOutcome::NotOpened) {
            $arrival = Arrival::query()->find($this->arrivalId);
            if ($arrival === null || $arrival->moto_stream_closed_at !== null || $arrival->moto_stream_opened_at === null) {
                return;
            }

            $dueAt = $arrival->stream_auto_close_at
                ?? ArrivalStreamAutoClose::dueAt($arrival, $arrival->moto_stream_opened_at);

            if ($dueAt !== null && $dueAt->isFuture()) {
                self::dispatch($this->arrivalId)->delay($dueAt);
            }
        }

        if ($outcome === CloseArrivalStreamOutcome::MotoFailed) {
            self::dispatch($this->arrivalId)->delay(now()->addMinute());
        }
    }
}
