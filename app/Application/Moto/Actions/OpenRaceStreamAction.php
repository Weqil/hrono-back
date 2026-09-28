<?php

namespace App\Application\Moto\Actions;

use App\Support\MotoApiHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class OpenRaceStreamAction
{
    /**
     * @return array{stream_id: ?string, stream_opened_at: ?Carbon}
     *
     * @throws RequestException
     */
    public function execute(int $raceId, string $bearerToken, string $arrivalName, ?int $arrivalTypeId = null): array
    {
        $baseUrl = rtrim((string) config('hrono.moto_api_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('MOTO_API_URL is not configured');
        }

        $url = "{$baseUrl}/races/{$raceId}/stream/open";

        $payload = [
            'arrival_name' => $arrivalName,
            'metadata' => [
                'arrival_type_id' => $arrivalTypeId,
            ],
        ];

        $response = MotoApiHttp::client($bearerToken)->post($url, $payload);

        Log::channel('info')->info('moto.stream.open', [
            'race_id' => $raceId,
            'arrival_name' => $arrivalName,
            'arrival_type_id' => $arrivalTypeId,
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        $response->throw();

        return $this->parseOpenResponse($response->json());
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @return array{stream_id: ?string, stream_opened_at: ?Carbon}
     */
    private function parseOpenResponse(?array $json): array
    {
        if ($json === null) {
            return ['stream_id' => null, 'stream_opened_at' => null];
        }

        $streamId = $json['stream_id'] ?? null;
        if ($streamId !== null && ! is_string($streamId)) {
            $streamId = is_numeric($streamId) ? (string) $streamId : null;
        }

        return [
            'stream_id' => is_string($streamId) && $streamId !== '' ? $streamId : null,
            'stream_opened_at' => $this->parseOpenedAt($json['stream_opened_at'] ?? null),
        ];
    }

    private function parseOpenedAt(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $num = (float) $value;

            if ($num >= 1_000_000_000_000) {
                return Carbon::createFromTimestampMs((int) round($num));
            }

            return Carbon::createFromTimestamp((int) round($num));
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        return null;
    }
}
