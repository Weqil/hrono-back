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
        private readonly AutoCloseArrivalStreamAction $autoCloseArrivalStream,
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

        if ($arrival->isStreamAutoCloseDue()) {
            $this->autoCloseArrivalStream->execute($arrival);
            $arrival->refresh();
        }

        $arrival->forceFill(['last_live_results_at' => now()])->save();

        if ($arrival->canOpenMotoStream()) {
            $outcome = $this->openArrivalStream->execute($id, $request);

            if ($outcome === OpenArrivalStreamOutcome::Opened) {
                $arrival->refresh();
            } else {
                Log::channel('info')->warning('arrivals.results.stream_open_skipped', [
                    'arrival_id' => $id,
                    'reason' => $outcome->name,
                ]);
            }
        }

        // Re-read: a concurrent stream/close must not be overwritten by a stale in-flight results POST.
        $arrival->refresh();

        if (! $arrival->canForwardLiveResultsToMoto()) {
            Log::channel('info')->warning('arrivals.results.moto_forward_skipped', [
                'arrival_id' => $id,
                'moto_race_id' => $arrival->moto_race_id,
                'reason' => 'stale_or_inactive_arrival',
                'stream_closed_at' => $arrival->moto_stream_closed_at,
                'stream_auto_close_at' => $arrival->stream_auto_close_at,
                'has_final_results' => $arrival->hasFinalResults(),
                'is_current_moto_stream' => $arrival->isCurrentMotoStream(),
            ]);

            return;
        }

        [$items, $countManualLaps] = self::parseLiveResultsPayload(
            $request->json()->all(),
            $request->query('count_manual_qualification_laps'),
        );
        $this->forwardResultsToMoto($id, $arrival, $request, $items, $countManualLaps);
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function forwardResultsToMoto(
        string $arrivalId,
        Arrival $arrival,
        Request $request,
        array $items,
        bool $countManualLaps,
    ): void
    {
        $bearer = MotoBearerExtractor::fromRequest($request);

        if ($bearer === null) {
            Log::channel('info')->warning('arrivals.results.moto_forward_skipped', [
                'arrival_id' => $arrivalId,
                'reason' => 'bearer_missing',
            ]);

            return;
        }

        $arrival->loadMissing('arrivalType');

        $reduced = ArrivalResultsReducer::reduce($items, $arrival->kind(), $countManualLaps);

        $streamOpenedAtMs = $arrival->moto_stream_opened_at?->getTimestampMs();

        $payload = [
            'arrival_meta' => [
                'race_id' => $arrival->moto_race_id,
                'arrival_name' => $arrival->name,
                'arrival_type_id' => $arrival->arrival_type_id,
                'arrival_type_slug' => $arrival->kind()?->value,
                'last_lap_number' => $reduced['last_lap_number'],
                'stream_opened_at' => $streamOpenedAtMs,
                'stream_id' => $arrival->moto_stream_id,
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

    /**
     * Accepts either a legacy participants list or
     * { participants: [...], count_manual_qualification_laps?: bool }.
     * Query/body flag overrides the default (count manuals = true).
     *
     * @param  array<int|string, mixed>  $body
     * @return array{0: array<int, mixed>, 1: bool}
     */
    private static function parseLiveResultsPayload(array $body, mixed $queryFlag = null): array
    {
        $countManualLaps = true;
        $items = $body;

        if (array_key_exists('participants', $body) && is_array($body['participants'])) {
            $items = $body['participants'];
            if (array_key_exists('count_manual_qualification_laps', $body)) {
                $countManualLaps = self::parseCountManualQualificationLaps(
                    $body['count_manual_qualification_laps'],
                );
            }
        }

        if ($queryFlag !== null && $queryFlag !== '') {
            $countManualLaps = self::parseCountManualQualificationLaps($queryFlag);
        }

        return [array_values($items), $countManualLaps];
    }

    private static function parseCountManualQualificationLaps(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return false;
            }
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
        }

        return true;
    }
}
