<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MotoApiHttp
{
    public static function client(string $bearerToken): PendingRequest
    {
        $secret = (string) config('hrono.api_secret', '');

        if ($secret === '') {
            throw new RuntimeException('API_SECRET is not configured');
        }

        return Http::withToken($bearerToken)
            ->acceptJson()
            ->timeout(15)
            ->withHeader('X-Api-Secret', $secret);
    }
}
