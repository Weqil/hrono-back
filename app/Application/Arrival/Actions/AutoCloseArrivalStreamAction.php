<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Application\Moto\Actions\CloseRaceStreamAction;
use App\Models\Arrival;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Closes an open stream after race duration + grace when nobody closed it.
 */
final class AutoCloseArrivalStreamAction
{
    public function __construct(
        private readonly CloseRaceStreamAction $closeRaceStream,
    ) {}

    public function execute(Arrival|string|int $arrival): CloseArrivalStreamOutcome
    {
        if (! $arrival instanceof Arrival) {
            $arrival = Arrival::query()->find($arrival);
        }

        if ($arrival === null) {
            return CloseArrivalStreamOutcome::ArrivalNotFound;
        }

        if ($arrival->moto_stream_closed_at !== null) {
            return CloseArrivalStreamOutcome::Closed;
        }

        if ($arrival->moto_stream_opened_at === null) {
            return CloseArrivalStreamOutcome::NotOpened;
        }

        if (! $arrival->isStreamAutoCloseDue()) {
            return CloseArrivalStreamOutcome::NotOpened;
        }

        $closedAt = now();

        if (! $arrival->isCurrentMotoStream()) {
            $this->markClosedLocally($arrival, $closedAt);

            return CloseArrivalStreamOutcome::Closed;
        }

        $bearer = $arrival->moto_stream_bearer;

        if (! is_string($bearer) || $bearer === '') {
            Log::channel('info')->warning('arrivals.stream.auto_close_local_only', [
                'arrival_id' => $arrival->getKey(),
                'reason' => 'bearer_missing',
            ]);
            $this->markClosedLocally($arrival, $closedAt);

            return CloseArrivalStreamOutcome::Closed;
        }

        try {
            $this->closeRaceStream->execute($arrival->moto_race_id, $bearer);
        } catch (RequestException $e) {
            Log::channel('info')->error('arrivals.stream.auto_close_failed', [
                'arrival_id' => $arrival->getKey(),
                'moto_race_id' => $arrival->moto_race_id,
                'status' => $e->response?->status(),
                'body' => $e->response?->json() ?? $e->response?->body(),
            ]);

            return CloseArrivalStreamOutcome::MotoFailed;
        } catch (RuntimeException $e) {
            Log::channel('info')->error('arrivals.stream.auto_close_failed', [
                'arrival_id' => $arrival->getKey(),
                'message' => $e->getMessage(),
            ]);

            return CloseArrivalStreamOutcome::MotoFailed;
        }

        $this->markClosedLocally($arrival, $closedAt);

        Log::channel('info')->info('arrivals.stream.auto_closed', [
            'arrival_id' => $arrival->getKey(),
            'moto_race_id' => $arrival->moto_race_id,
        ]);

        return CloseArrivalStreamOutcome::Closed;
    }

    private function markClosedLocally(Arrival $arrival, mixed $closedAt): void
    {
        $arrival->forceFill([
            'moto_stream_closed_at' => $closedAt,
            'moto_stream_id' => null,
            'stream_auto_close_at' => null,
            'moto_stream_bearer' => null,
        ])->save();
    }
}
