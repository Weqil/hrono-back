<?php

namespace App\Support;

final class QualificationBestLap
{
    /**
     * @param  array<string, mixed>  $lap
     */
    public static function lapTimeMs(array $lap): int
    {
        return (int) ($lap['lapTimeMs'] ?? $lap['lap_time_ms'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $lap
     */
    public static function timestampMs(array $lap): int
    {
        return (int) ($lap['timestampMs'] ?? $lap['timestamp_ms'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $lap
     */
    public static function lapNumber(array $lap, int $index): int
    {
        $value = $lap['lapNumber'] ?? $lap['lap_number'] ?? null;

        return $value !== null ? (int) $value : $index + 1;
    }

    /**
     * @param  array<string, mixed>  $lap
     */
    public static function isManual(array $lap): bool
    {
        return filter_var($lap['isManual'] ?? $lap['is_manual'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<int, array<string, mixed>>  $laps
     */
    public static function isEligibleForBest(array $laps, int $index): bool
    {
        $lap = $laps[$index] ?? null;
        if (! is_array($lap)) {
            return false;
        }

        if (self::lapTimeMs($lap) <= 0) {
            return false;
        }

        if (self::isManual($lap)) {
            return false;
        }

        if ($index > 0 && self::isManual($laps[$index - 1])) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, array<string, mixed>>  $laps
     * @return array{lap_time_ms:int, timestamp_ms:int, lap_number:int}|null
     */
    public static function getBestLap(array $laps): ?array
    {
        if ($laps === []) {
            return null;
        }

        $best = null;

        foreach ($laps as $index => $lap) {
            if (! is_array($lap) || ! self::isEligibleForBest($laps, $index)) {
                continue;
            }

            $candidate = [
                'lap_time_ms' => self::lapTimeMs($lap),
                'timestamp_ms' => self::timestampMs($lap),
                'lap_number' => self::lapNumber($lap, $index),
            ];

            if (
                $best === null
                || $candidate['lap_time_ms'] < $best['lap_time_ms']
                || (
                    $candidate['lap_time_ms'] === $best['lap_time_ms']
                    && $candidate['timestamp_ms'] < $best['timestamp_ms']
                )
            ) {
                $best = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param  array<int, array<string, mixed>>  $laps
     */
    public static function getBestLapTimeMs(array $laps): ?int
    {
        return self::getBestLap($laps)['lap_time_ms'] ?? null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $laps
     */
    public static function getBestLapTimestampMs(array $laps): ?int
    {
        return self::getBestLap($laps)['timestamp_ms'] ?? null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lapsA
     * @param  array<int, array<string, mixed>>  $lapsB
     */
    public static function compareBestLaps(array $lapsA, array $lapsB): int
    {
        $bestA = self::getBestLap($lapsA);
        $bestB = self::getBestLap($lapsB);
        $aHasBest = $bestA !== null;
        $bHasBest = $bestB !== null;

        if ($aHasBest && ! $bHasBest) {
            return -1;
        }

        if (! $aHasBest && $bHasBest) {
            return 1;
        }

        if (! $aHasBest && ! $bHasBest) {
            return 0;
        }

        if ($bestA['lap_time_ms'] !== $bestB['lap_time_ms']) {
            return $bestA['lap_time_ms'] <=> $bestB['lap_time_ms'];
        }

        return $bestA['timestamp_ms'] <=> $bestB['timestamp_ms'];
    }

    /**
     * Best eligible lap time among all laps except the last one (for last-lap delta).
     *
     * @param  array<int, array<string, mixed>>  $laps
     */
    public static function referenceBestLapTimeMs(array $laps, int $lastLapTimeMs): int
    {
        if ($laps === []) {
            return $lastLapTimeMs;
        }

        $eligiblePreviousTimes = [];

        foreach ($laps as $index => $lap) {
            if ($index === count($laps) - 1) {
                continue;
            }

            if (! is_array($lap) || ! self::isEligibleForBest($laps, $index)) {
                continue;
            }

            $eligiblePreviousTimes[] = self::lapTimeMs($lap);
        }

        if ($eligiblePreviousTimes === []) {
            return $lastLapTimeMs;
        }

        return min($eligiblePreviousTimes);
    }
}
