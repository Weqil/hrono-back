<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyApiSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('hrono.api_secret', '');

        if ($expected === '') {
            abort(503, __('api.secret_not_configured'));
        }

        $provided = $this->extractSecret($request);

        if ($provided === null || ! hash_equals($expected, $provided)) {
            abort(401, __('api.invalid_or_missing_secret'));
        }

        return $next($request);
    }

    private function extractSecret(Request $request): ?string
    {
        $header = $request->header('X-Api-Secret');
        if (is_string($header) && $header !== '') {
            return $header;
        }

        $auth = $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            $token = trim(substr($auth, 7));

            return $token !== '' ? $token : null;
        }

        return null;
    }
}
