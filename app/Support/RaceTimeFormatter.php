<?php

namespace App\Support;

final class RaceTimeFormatter
{
    public static function formatMs(?int $milliseconds): string
    {
        if ($milliseconds === null || $milliseconds < 0) {
            return '00:00:00';
        }

        $totalSeconds = intdiv($milliseconds, 1000);
        $millis = $milliseconds % 1000;
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        $formatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        if ($millis > 0) {
            $formatted .= sprintf('.%03d', $millis);
        }

        return $formatted;
    }

    public static function parseToMs(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $fraction = '000';

        if (str_contains($value, '.')) {
            [$value, $fraction] = explode('.', $value, 2);
            $fraction = str_pad(substr($fraction, 0, 3), 3, '0', STR_PAD_RIGHT);
        }

        $segments = array_map(intval(...), explode(':', $value));

        $totalSeconds = match (count($segments)) {
            3 => ($segments[0] * 3600) + ($segments[1] * 60) + $segments[2],
            2 => ($segments[0] * 60) + $segments[1],
            1 => $segments[0],
            default => null,
        };

        if ($totalSeconds === null) {
            return null;
        }

        return ($totalSeconds * 1000) + (int) $fraction;
    }
}
