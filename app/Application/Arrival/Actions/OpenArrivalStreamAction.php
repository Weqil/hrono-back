<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Application\Arrival\Enums\OpenArrivalStreamOutcome;
use App\Application\Moto\Actions\OpenRaceStreamAction;
use App\Jobs\AutoCloseArrivalStreamJob;
use App\Models\Arrival;
use App\Support\ArrivalStreamAutoClose;
use App\Support\ArrivalStreamBearerStore;
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
                'moto_stream_id' => null,
            ])->save();
        }

        try {
            $motoOpen = $this->openRaceStream->execute(
                $arrival->moto_race_id,
                $bearer,
                $arrival->name,
                $arrival->arrival_type_id,
            );
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

        $openedAt = $motoOpen['stream_opened_at']
            ?? RequestTimeParser::fromRequest($request)
            ?? now();
        $autoCloseAt = ArrivalStreamAutoClose::dueAt($arrival, $openedAt);

        $arrival->forceFill([
            'moto_stream_opened_at' => $openedAt,
            'moto_stream_closed_at' => null,
            'moto_stream_id' => $motoOpen['stream_id'],
            'stream_auto_close_at' => $autoCloseAt,
            'moto_stream_bearer' => $bearer,
        ])->save();

        ArrivalStreamBearerStore::put($arrival, $bearer, $autoCloseAt);

        Log::channel('info')->info('arrivals.stream.opened', [
            'arrival_id' => $arrivalId,
            'moto_race_id' => $arrival->moto_race_id,
            'arrival_time' => $arrival->time,
            'stream_opened_at' => $openedAt->toIso8601String(),
            'stream_auto_close_at' => $autoCloseAt?->toIso8601String(),
            'moto_stream_id' => $motoOpen['stream_id'],
        ]);

        AutoCloseArrivalStreamJob::dispatch($arrival->getKey())
            ->delay($autoCloseAt);

        return OpenArrivalStreamOutcome::Opened;
    }
}
