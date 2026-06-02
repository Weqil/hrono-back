<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\OpenArrivalStreamOutcome;
use App\Application\Moto\Actions\OpenRaceStreamAction;
use App\Models\Arrival;
use App\Support\MotoBearerExtractor;
use App\Support\RequestTimeParser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class OpenArrivalStreamAction
{
    public function __construct(
        private readonly OpenRaceStreamAction $openRaceStream,
    ) {}

    public function execute(string $arrivalId, Request $request): OpenArrivalStreamOutcome
    {
        $arrival = Arrival::query()->find($arrivalId);

        if ($arrival === null) {
            return OpenArrivalStreamOutcome::ArrivalNotFound;
        }

        if ($arrival->moto_stream_closed_at !== null) {
            return OpenArrivalStreamOutcome::AlreadyClosed;
        }

        if ($arrival->moto_stream_opened_at !== null) {
            return OpenArrivalStreamOutcome::AlreadyOpened;
        }

        $bearer = MotoBearerExtractor::fromRequest($request);

        if ($bearer === null) {
            return OpenArrivalStreamOutcome::BearerMissing;
        }

        try {
            $this->openRaceStream->execute($arrival->moto_race_id, $bearer);
        } catch (RequestException $e) {
            Log::channel('info')->error('arrivals.stream.open_failed', [
                'arrival_id' => $arrivalId,
                'moto_race_id' => $arrival->moto_race_id,
                'status' => $e->response?->status(),
                'body' => $e->response?->json() ?? $e->response?->body(),
            ]);

            return OpenArrivalStreamOutcome::MotoFailed;
        } catch (RuntimeException $e) {
            Log::channel('info')->error('arrivals.stream.open_failed', [
                'arrival_id' => $arrivalId,
                'message' => $e->getMessage(),
            ]);

            return OpenArrivalStreamOutcome::MotoFailed;
        }

        $openedAt = RequestTimeParser::fromRequest($request) ?? now();
        $arrival->forceFill(['moto_stream_opened_at' => $openedAt])->save();

        return OpenArrivalStreamOutcome::Opened;
    }
}
