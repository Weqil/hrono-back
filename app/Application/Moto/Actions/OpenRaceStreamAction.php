<?php

namespace App\Application\Moto\Actions;

use App\Support\MotoApiHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class OpenRaceStreamAction
{
    /**
     * @throws RequestException
     */
    public function execute(int $raceId, string $bearerToken, string $arrivalName, ?int $arrivalTypeId = null): void
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
    }
}
