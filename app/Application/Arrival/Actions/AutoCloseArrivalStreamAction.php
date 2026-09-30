<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Application\Moto\Actions\CloseRaceStreamAction;
use App\Models\Arrival;
use App\Support\ArrivalStreamAutoClose;
use App\Support\ArrivalStreamBearerStore;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Closes an open stream after race duration + idle grace when nobody closed it.
 * Idle grace resets on live results (see ArrivalStreamAutoClose::extendFromActivity).
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
            Log::channel('info')->info('arrivals.stream.auto_close_not_due', [
                'arrival_id' => $arrival->getKey(),
                'arrival_time' => $arrival->time,
                'stream_opened_at' => $arrival->moto_stream_opened_at?->toIso8601String(),
                'stream_auto_close_at' => ($arrival->stream_auto_close_at
                    ?? ArrivalStreamAutoClose::dueAt($arrival, $arrival->moto_stream_opened_at)
                )?->toIso8601String(),
            ]);

            return CloseArrivalStreamOutcome::NotOpened;
        }

        $closedAt = now();

        if (! $arrival->isCurrentMotoStream()) {
            $this->markClosedLocally($arrival, $closedAt);

            return CloseArrivalStreamOutcome::Closed;
        }

        $bearer = $this->resolveBearer($arrival);

        if ($bearer === null) {
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

            if ($e->response !== null && $e->response->clientError()) {
                $this->markClosedLocally($arrival, $closedAt);

                return CloseArrivalStreamOutcome::Closed;
            }

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

    private function resolveBearer(Arrival $arrival): ?string
    {
        $fromCache = ArrivalStreamBearerStore::get($arrival);
        if ($fromCache !== null) {
            return $fromCache;
        }

        $raw = $arrival->getAttributes()['moto_stream_bearer'] ?? null;
        // Skip Laravel encrypted payloads left from older releases (eyJpdiI...)
        if (is_string($raw) && $raw !== '' && ! str_starts_with($raw, 'eyJpdiI')) {
            return $raw;
        }

        $fallback = (string) config('hrono.moto_service_bearer', '');

        return $fallback !== '' ? $fallback : null;
    }

    private function markClosedLocally(Arrival $arrival, mixed $closedAt): void
    {
        ArrivalStreamBearerStore::forget($arrival);

        $arrival->forceFill([
            'moto_stream_closed_at' => $closedAt,
            'moto_stream_id' => null,
            'stream_auto_close_at' => null,
            'moto_stream_bearer' => null,
        ])->save();
    }
}
