<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
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
        private readonly CloseArrivalStreamAction $closeArrivalStream,
    ) {}

    public function execute(string $arrivalId, Request $request): OpenArrivalStreamOutcome
    {
        $arrival = Arrival::query()->find($arrivalId);

        if ($arrival === null) {
            return OpenArrivalStreamOutcome::ArrivalNotFound;
        }

        $bearer = MotoBearerExtractor::fromRequest($request);

        if ($bearer === null) {
            return OpenArrivalStreamOutcome::BearerMissing;
        }

        $openArrivals = Arrival::query()
            ->where('moto_race_id', $arrival->moto_race_id)
            ->whereNotNull('moto_stream_opened_at')
            ->whereNull('moto_stream_closed_at')
            ->get();

        foreach ($openArrivals as $openArrival) {
            $closeOutcome = $this->closeArrivalStream->execute((string) $openArrival->getKey(), $request);

            if ($closeOutcome === CloseArrivalStreamOutcome::MotoFailed) {
                return OpenArrivalStreamOutcome::MotoFailed;
            }
        }

        $arrival->refresh();

        if ($arrival->moto_stream_closed_at !== null) {
            $arrival->forceFill([
                'moto_stream_opened_at' => null,
                'moto_stream_closed_at' => null,
            ])->save();
        }

        try {
            $this->openRaceStream->execute($arrival->moto_race_id, $bearer, $arrival->name);
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
        $arrival->forceFill([
            'moto_stream_opened_at' => $openedAt,
            'moto_stream_closed_at' => null,
        ])->save();

        return OpenArrivalStreamOutcome::Opened;
    }
}
