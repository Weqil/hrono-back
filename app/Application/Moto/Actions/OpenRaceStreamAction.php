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
    public function execute(int $raceId, string $bearerToken, string $arrivalName): void
    {
        $baseUrl = rtrim((string) config('hrono.moto_api_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('MOTO_API_URL is not configured');
        }

        $url = "{$baseUrl}/races/{$raceId}/stream/open";

        $response = MotoApiHttp::client($bearerToken)->post($url, [
            'arrival_name' => $arrivalName,
        ]);

        Log::channel('info')->info('moto.stream.open', [
            'race_id' => $raceId,
            'arrival_name' => $arrivalName,
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        $response->throw();
    }
}
