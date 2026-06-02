<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class RequestTimeParser
{
    /**
     * Parses `time` from request body.
     *
     * Supported:
     * - seconds timestamp (10 digits-ish)
     * - milliseconds timestamp (13 digits-ish)
     * - ISO string (e.g. 2026-06-02T12:00:00Z)
     */
    public static function fromRequest(Request $request): ?CarbonImmutable
    {
        $value = $request->input('time');

        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $num = (float) $value;

            // treat large values as milliseconds
            if ($num >= 1_000_000_000_000) {
                return CarbonImmutable::createFromTimestampMs((int) round($num));
            }

            return CarbonImmutable::createFromTimestamp((int) round($num));
        }

        if (is_string($value) && trim($value) !== '') {
            return CarbonImmutable::parse($value);
        }

        return null;
    }
}

