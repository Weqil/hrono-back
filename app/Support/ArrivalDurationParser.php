<?php

namespace App\Support;

/**
 * Parses arrival `time` duration strings used by checkpoint clients.
 *
 * Supported:
 * - HH:MM:SS (e.g. 00:10:00 → 600s)
 * - MM:SS (e.g. 10:00 → 600s)
 * - integer seconds as string/number
 */
final class ArrivalDurationParser
{
    public static function toSeconds(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $seconds = (int) round($value);

            return $seconds >= 0 ? $seconds : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (is_numeric($trimmed)) {
            $seconds = (int) round((float) $trimmed);

            return $seconds >= 0 ? $seconds : null;
        }

        if (! preg_match('/^(\d{1,3}):(\d{1,2})(?::(\d{1,2}))?$/', $trimmed, $matches)) {
            return null;
        }

        $first = (int) $matches[1];
        $second = (int) $matches[2];
        $hasThird = array_key_exists(3, $matches) && $matches[3] !== null && $matches[3] !== '';

        if ($hasThird) {
            $hours = $first;
            $minutes = $second;
            $seconds = (int) $matches[3];
        } else {
            $hours = 0;
            $minutes = $first;
            $seconds = $second;
        }

        if ($minutes > 59 || $seconds > 59) {
            return null;
        }

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}
