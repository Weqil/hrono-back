<?php

namespace App\Support;

use App\Models\Arrival;
use Illuminate\Support\Facades\Cache;

/**
 * Stores Motо bearer for auto-close outside encrypted DB casts so worker/scheduler
 * can close Motо even when APP_KEY differs between services (common Swarmpit mistake).
 */
final class ArrivalStreamBearerStore
{
    public static function put(Arrival $arrival, string $bearer, mixed $ttlUntil): void
    {
        $until = \Illuminate\Support\Carbon::parse($ttlUntil);
        $seconds = (int) now()->diffInSeconds($until, absolute: false);
        // Keep bearer a bit longer than the auto-close deadline.
        $ttl = max(120, $seconds + 3600);

        Cache::put(self::key($arrival), $bearer, $ttl);
    }

    public static function get(Arrival $arrival): ?string
    {
        $value = Cache::get(self::key($arrival));

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function forget(Arrival $arrival): void
    {
        Cache::forget(self::key($arrival));
    }

    private static function key(Arrival $arrival): string
    {
        return 'hrono:arrival_stream_bearer:'.$arrival->getKey();
    }
}
