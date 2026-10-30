<?php

namespace App\Support;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\Arrival;

/**
 * Maps a live IParticipantState-like snapshot into final-results rows
 * (same shape as POST /arrivals/{id}/final-results → results[]).
 */
final class LiveResultsToFinalResultsMapper
{
    /**
     * @param  array<int, mixed>  $items
     * @return list<array{
     *     place: int,
     *     total_laps: int,
     *     total_time_ms: int,
     *     best_lap_time_ms: int,
     *     user: array{
     *         id: int,
     *         name: string,
     *         surname: string,
     *         patronymic: string,
     *         start_number: int,
     *         tag_id: string,
     *         grade: string,
     *         command: string
     *     },
     *     laps: list<array{
     *         lap_number: int,
     *         lap_time_ms: int,
     *         timestamp_ms: int,
     *         position_on_lap: int,
     *         is_manual: bool
     *     }>
     * }>
     */
    public static function map(Arrival $arrival, array $items): array
    {
        $kind = $arrival->kind();
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $pData = $item['participantData'] ?? null;
            if (! is_array($pData)) {
                continue;
            }

            $userId = (int) ($pData['id'] ?? $item['id'] ?? 0);
            if ($userId < 1) {
                continue;
            }

            $lapsRaw = is_array($item['laps'] ?? null) ? $item['laps'] : [];
            $laps = [];
            foreach (array_values($lapsRaw) as $index => $lap) {
                if (! is_array($lap)) {
                    continue;
                }

                $laps[] = [
                    'lap_number' => QualificationBestLap::lapNumber($lap, $index),
                    'lap_time_ms' => QualificationBestLap::lapTimeMs($lap),
                    'timestamp_ms' => QualificationBestLap::timestampMs($lap),
                    'position_on_lap' => (int) ($lap['position_on_lap'] ?? $lap['positionOnLap'] ?? 0),
                    'is_manual' => QualificationBestLap::isManual($lap),
                ];
            }

            $bestLapTimeMs = self::bestLapTimeMs($laps, $kind);

            $normalized[] = [
                'user_id' => $userId,
                'lap_count' => (int) ($item['lapCount'] ?? count($laps)),
                'total_time_ms' => (int) ($item['totalRaceTimeMs'] ?? $item['total_race_time_ms'] ?? 0),
                'best_lap_time_ms' => $bestLapTimeMs,
                'best_lap_timestamp_ms' => self::bestLapTimestampMs($laps, $bestLapTimeMs),
                'user' => [
                    'id' => $userId,
                    'name' => (string) ($pData['name'] ?? ''),
                    'surname' => (string) ($pData['surname'] ?? ''),
                    'patronymic' => (string) ($pData['patronymic'] ?? ''),
                    'start_number' => (int) ($pData['start_number'] ?? $pData['startNumber'] ?? 0),
                    'tag_id' => (string) ($pData['tag_id'] ?? $pData['tagId'] ?? ''),
                    'grade' => (string) ($pData['grade'] ?? ''),
                    'command' => (string) ($pData['command'] ?? ''),
                ],
                'laps' => $laps,
            ];
        }

        usort($normalized, static function (array $a, array $b) use ($kind): int {
            if ($kind === ArrivalKind::Qualification) {
                $aBest = $a['best_lap_time_ms'] > 0 ? $a['best_lap_time_ms'] : PHP_INT_MAX;
                $bBest = $b['best_lap_time_ms'] > 0 ? $b['best_lap_time_ms'] : PHP_INT_MAX;
                if ($aBest !== $bBest) {
                    return $aBest <=> $bBest;
                }

                return $a['best_lap_timestamp_ms'] <=> $b['best_lap_timestamp_ms'];
            }

            if ($a['lap_count'] !== $b['lap_count']) {
                return $b['lap_count'] <=> $a['lap_count'];
            }

            return $a['total_time_ms'] <=> $b['total_time_ms'];
        });

        $rows = [];
        foreach ($normalized as $index => $row) {
            $rows[] = [
                'place' => $index + 1,
                'total_laps' => $row['lap_count'],
                'total_time_ms' => $row['total_time_ms'],
                'best_lap_time_ms' => $row['best_lap_time_ms'],
                'user' => $row['user'],
                'laps' => $row['laps'],
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{lap_number: int, lap_time_ms: int, timestamp_ms: int, position_on_lap: int, is_manual: bool}>  $laps
     */
    private static function bestLapTimeMs(array $laps, ?ArrivalKind $kind): int
    {
        if ($laps === []) {
            return 0;
        }

        if ($kind === ArrivalKind::Qualification) {
            $best = QualificationBestLap::getBestLapTimeMs($laps);

            return $best !== null ? (int) $best : 0;
        }

        $times = array_values(array_filter(
            array_map(static fn (array $lap): int => $lap['lap_time_ms'], $laps),
            static fn (int $ms): bool => $ms > 0,
        ));

        return $times === [] ? 0 : min($times);
    }

    /**
     * @param  list<array{lap_number: int, lap_time_ms: int, timestamp_ms: int, position_on_lap: int, is_manual: bool}>  $laps
     */
    private static function bestLapTimestampMs(array $laps, int $bestLapTimeMs): int
    {
        if ($bestLapTimeMs <= 0) {
            return PHP_INT_MAX;
        }

        foreach ($laps as $lap) {
            if ($lap['lap_time_ms'] === $bestLapTimeMs) {
                return $lap['timestamp_ms'];
            }
        }

        return PHP_INT_MAX;
    }
}
