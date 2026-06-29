<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\OpenArrivalStreamOutcome;
use App\Application\Moto\Actions\SendRaceResultsToMotoAction;
use App\Models\Arrival;
use App\Support\ArrivalResultsReducer;
use App\Support\MotoBearerExtractor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class ReceiveArrivalAction
{
    public function __construct(
        private readonly SendRaceResultsToMotoAction $sendResultsToMoto,
        private readonly OpenArrivalStreamAction $openArrivalStream,
    ) {}

    public function execute(string $id, Request $request): void
    {
        Log::channel('info')->info('arrivals.results', [
            'arrival_id' => $id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'query' => $request->query(),
            'json' => $request->json()->all(),
            'raw_body' => $request->getContent(),
        ]);

        $arrival = Arrival::query()->find($id);

        if ($arrival === null) {
            Log::channel('info')->warning('arrivals.results.arrival_not_found', ['arrival_id' => $id]);

            return;
        }

        $this->forwardResultsToMoto($id, $arrival, $request);

        if (! $arrival->canOpenMotoStream()) {
            return;
        }

        $outcome = $this->openArrivalStream->execute($id, $request);

        if ($outcome === OpenArrivalStreamOutcome::Opened) {
            return;
        }

        Log::channel('info')->warning('arrivals.results.stream_open_skipped', [
            'arrival_id' => $id,
            'reason' => $outcome->name,
        ]);
    }

    private function forwardResultsToMoto(string $arrivalId, Arrival $arrival, Request $request): void
    {
        $bearer = MotoBearerExtractor::fromRequest($request);

        if ($bearer === null) {
            Log::channel('info')->warning('arrivals.results.moto_forward_skipped', [
                'arrival_id' => $arrivalId,
                'reason' => 'bearer_missing',
            ]);

            return;
        }

        $reduced = ArrivalResultsReducer::reduce($request->json()->all());

        $streamOpenedAtMs = $arrival->moto_stream_opened_at?->getTimestampMs();

        $payload = [
            'arrival_meta' => [
                'race_id' => $arrival->moto_race_id,
                'arrival_name' => $arrival->name,
                'last_lap_number' => $reduced['last_lap_number'],
                'stream_opened_at' => $streamOpenedAtMs,
            ],
            'participants' => $reduced['participants'],
        ];

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (! is_string($jsonBody)) {
            Log::channel('info')->error('arrivals.results.moto_forward_failed', [
                'arrival_id' => $arrivalId,
                'moto_race_id' => $arrival->moto_race_id,
                'message' => 'Failed to encode reduced results to JSON',
            ]);

            return;
        }

        try {
            $this->sendResultsToMoto->execute($arrival->moto_race_id, $bearer, $jsonBody);
        } catch (ConnectionException $e) {
            Log::channel('info')->error('arrivals.results.moto_forward_failed', [
                'arrival_id' => $arrivalId,
                'moto_race_id' => $arrival->moto_race_id,
                'message' => $e->getMessage(),
            ]);
        } catch (RequestException $e) {
            Log::channel('info')->error('arrivals.results.moto_forward_failed', [
                'arrival_id' => $arrivalId,
                'moto_race_id' => $arrival->moto_race_id,
                'status' => $e->response?->status(),
                'body' => $e->response?->json() ?? $e->response?->body(),
            ]);
        } catch (RuntimeException $e) {
            Log::channel('info')->error('arrivals.results.moto_forward_failed', [
                'arrival_id' => $arrivalId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
