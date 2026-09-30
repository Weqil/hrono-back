<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Application\Moto\Actions\CloseRaceStreamAction;
use App\Models\Arrival;
use App\Support\ArrivalStreamBearerStore;
use App\Support\MotoBearerExtractor;
use App\Support\RequestTimeParser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Закрывает трансляцию в Moto. Не сохраняет и не изменяет финальные результаты.
 */
final class CloseArrivalStreamAction
{
    public function __construct(
        private readonly CloseRaceStreamAction $closeRaceStream,
    ) {}

    /**
     * Закрывает трансляцию в Moto. Не сохраняет финальные результаты заезда.
     */
    public function execute(string $arrivalId, Request $request): CloseArrivalStreamOutcome
    {
        $arrival = Arrival::query()->find($arrivalId);

        if ($arrival === null) {
            return CloseArrivalStreamOutcome::ArrivalNotFound;
        }

        if ($arrival->moto_stream_closed_at !== null) {
            return CloseArrivalStreamOutcome::Closed;
        }

        if ($arrival->moto_stream_opened_at === null) {
            return CloseArrivalStreamOutcome::NotOpened;
        }

        $closedAt = RequestTimeParser::fromRequest($request) ?? now();

        // Moto stream is per race: close it only when this arrival is the current active one.
        // If another arrival holds the open stream, do not call Moto — only clear a stale local flag.
        if (! $arrival->isCurrentMotoStream()) {
            $this->markClosedLocally($arrival, $closedAt);

            return CloseArrivalStreamOutcome::Closed;
        }

        $bearer = MotoBearerExtractor::fromRequest($request);

        if ($bearer === null) {
            return CloseArrivalStreamOutcome::BearerMissing;
        }

        try {
            $this->closeRaceStream->execute($arrival->moto_race_id, $bearer);
        } catch (RequestException $e) {
            Log::channel('info')->error('arrivals.stream.close_failed', [
                'arrival_id' => $arrivalId,
                'moto_race_id' => $arrival->moto_race_id,
                'status' => $e->response?->status(),
                'body' => $e->response?->json() ?? $e->response?->body(),
            ]);

            return CloseArrivalStreamOutcome::MotoFailed;
        } catch (RuntimeException $e) {
            Log::channel('info')->error('arrivals.stream.close_failed', [
                'arrival_id' => $arrivalId,
                'message' => $e->getMessage(),
            ]);

            return CloseArrivalStreamOutcome::MotoFailed;
        }

        $this->markClosedLocally($arrival, $closedAt);

        return CloseArrivalStreamOutcome::Closed;
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
