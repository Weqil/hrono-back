<?php

namespace App\Application\Moto\Actions;

use App\Support\MotoApiHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class CloseRaceStreamAction
{
    /**
     * @throws RequestException
     */
    public function execute(int $raceId, string $bearerToken): void
    {
        $baseUrl = rtrim((string) config('hrono.moto_api_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('MOTO_API_URL is not configured');
        }

        $url = "{$baseUrl}/races/{$raceId}/stream/close";

        $response = MotoApiHttp::client($bearerToken)->post($url);

        Log::channel('info')->info('moto.stream.close', [
            'race_id' => $raceId,
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        $response->throw();
    }
}
