<?php

namespace App\Application\Moto\Actions;

use App\Support\MotoApiHttp;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class SendRaceResultsToMotoAction
{
    /**
     * @throws RequestException
     */
    public function execute(int $raceId, string $bearerToken, string $jsonBody): void
    {
        $baseUrl = rtrim((string) config('hrono.moto_api_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('MOTO_API_URL is not configured');
        }

        $url = "{$baseUrl}/hrono/races/{$raceId}/results";

        $response = MotoApiHttp::client($bearerToken)
            ->withBody($jsonBody, 'application/json')
            ->post($url);

        Log::channel('info')->info('moto.results.forward', [
            'race_id' => $raceId,
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        $response->throw();
    }
}
