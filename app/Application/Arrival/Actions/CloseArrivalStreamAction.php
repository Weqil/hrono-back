<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Application\Moto\Actions\CloseRaceStreamAction;
use App\Models\Arrival;
use App\Support\MotoBearerExtractor;
use App\Support\RequestTimeParser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class CloseArrivalStreamAction
{
    public function __construct(
        private readonly CloseRaceStreamAction $closeRaceStream,
    ) {}

    public function execute(string $arrivalId, Request $request): CloseArrivalStreamOutcome
    {
        $arrival = Arrival::query()->find($arrivalId);

        if ($arrival === null) {
            return CloseArrivalStreamOutcome::ArrivalNotFound;
        }

        if ($arrival->moto_stream_closed_at !== null) {
            return CloseArrivalStreamOutcome::AlreadyClosed;
        }

        if ($arrival->moto_stream_opened_at === null) {
            return CloseArrivalStreamOutcome::NotOpened;
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

        $closedAt = RequestTimeParser::fromRequest($request) ?? now();
        $arrival->forceFill(['moto_stream_closed_at' => $closedAt])->save();

        return CloseArrivalStreamOutcome::Closed;
    }
}
