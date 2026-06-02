<?php

namespace App\Support;

use Illuminate\Http\Request;

final class MotoBearerExtractor
{
    public static function fromRequest(Request $request): ?string
    {
        $apiSecret = (string) config('hrono.api_secret', '');

        $authorization = $request->header('Authorization', '');
        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            $token = trim(substr($authorization, 7));
            if ($token !== '' && ($apiSecret === '' || ! hash_equals($apiSecret, $token))) {
                return $token;
            }
        }

        foreach (['bearer', 'moto_bearer', 'moto_token', 'token'] as $key) {
            $value = $request->input($key);
            if (! is_string($value) || $value === '') {
                continue;
            }

            if (str_starts_with($value, 'Bearer ')) {
                $value = trim(substr($value, 7));
            }

            if ($value !== '' && ($apiSecret === '' || ! hash_equals($apiSecret, $value))) {
                return $value;
            }
        }

        return null;
    }
}
