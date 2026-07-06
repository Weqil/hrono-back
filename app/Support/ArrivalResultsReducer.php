<?php

namespace App\Support;

use App\Application\Arrival\Enums\ArrivalKind;

final class ArrivalResultsReducer
{
    /**
     * @param  array<int, mixed>  $items  Массив участников из check-point, структура IParticipantState
     * @return array{last_lap_number:int, participants:array<int, mixed>} Reduced & sorted payload parts for Moto.
     */
    public static function reduce(array $items, ?ArrivalKind $kind = null): array
    {
        if ($kind === ArrivalKind::Qualification) {
            return self::reduceQualification($items);
        }

        return self::reduceRegular($items);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array{last_lap_number:int, participants:array<int, mixed>}
     */
    private static function reduceRegular(array $items): array
    {
        $processedParticipants = [];
        $overallMaxLapNumber = 0;

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['participantData']) || ! is_array($item['participantData'])) {
                continue;
            }

            $laps = $item['laps'] ?? [];
            $lapTimes = array_column(array_filter($laps, fn ($lap) => ($lap['lapTimeMs'] ?? 0) > 0), 'lapTimeMs');
            $averageLapTimeMs = count($lapTimes) > 0 ? array_sum($lapTimes) / count($lapTimes) : 0;
            $lastLapNumber = (int) ($item['lapCount'] ?? 0);

            $processedParticipants[] = [
                'participantData' => $item['participantData'],
                'last_lap_number' => $lastLapNumber,
                'total_race_time_ms' => (int) ($item['totalRaceTimeMs'] ?? 0),
                'last_lap_timestamp_ms' => (int) ($item['lastLapTimestampMs'] ?? 0),
                'average_lap_time_ms' => $averageLapTimeMs,
            ];

            if ($lastLapNumber > $overallMaxLapNumber) {
                $overallMaxLapNumber = $lastLapNumber;
            }
        }

        if (empty($processedParticipants)) {
            return ['last_lap_number' => 0, 'participants' => []];
        }

        $leader = null;
        foreach ($processedParticipants as $participant) {
            if ($leader === null ||
                $participant['last_lap_number'] > $leader['last_lap_number'] ||
                ($participant['last_lap_number'] === $leader['last_lap_number'] && $participant['total_race_time_ms'] < $leader['total_race_time_ms'])
            ) {
                $leader = $participant;
            }
        }

        if ($leader === null) {
            return ['last_lap_number' => $overallMaxLapNumber, 'participants' => []];
        }

        $finalParticipants = [];
        foreach ($processedParticipants as $participant) {
            $lapDiff = $leader['last_lap_number'] - $participant['last_lap_number'];

            $displayTimeMs = 0;
            if ($lapDiff === 0) {
                $displayTimeMs = $participant['total_race_time_ms'] - $leader['total_race_time_ms'];
            }

            $pData = $participant['participantData'];
            $finalParticipants[] = [
                'id' => $pData['id'] ?? null,
                'lapCount' => $participant['last_lap_number'],
                'lastLapTimestampMs' => $participant['last_lap_timestamp_ms'],
                'totalRaceTimeMs' => $participant['total_race_time_ms'],
                'position' => 0,
                'displayTimeMs' => $displayTimeMs,
                'laps_behind' => $lapDiff,
                'participantData' => self::participantData($pData),
            ];
        }

        usort($finalParticipants, static function ($a, $b): int {
            $lapDiff = $b['lapCount'] <=> $a['lapCount'];
            if ($lapDiff !== 0) {
                return $lapDiff;
            }

            return $a['totalRaceTimeMs'] <=> $b['totalRaceTimeMs'];
        });

        foreach ($finalParticipants as $idx => &$p) {
            $p['position'] = $idx + 1;
        }
        unset($p);

        $filteredFinalParticipants = array_values(array_filter(
            $finalParticipants,
            static fn (array $finalParticipant): bool => $finalParticipant['lapCount'] > 0,
        ));

        return [
            'last_lap_number' => $overallMaxLapNumber,
            'participants' => $filteredFinalParticipants,
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array{last_lap_number:int, participants:array<int, mixed>}
     */
    private static function reduceQualification(array $items): array
    {
        $processedParticipants = [];
        $overallMaxLapNumber = 0;

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['participantData']) || ! is_array($item['participantData'])) {
                continue;
            }

            $validLaps = array_values(array_filter(
                $item['laps'] ?? [],
                static fn ($lap): bool => is_array($lap) && (int) ($lap['lapTimeMs'] ?? 0) > 0,
            ));
            $startTimestampMs = (int) ($item['startTimestampMs'] ?? 0);
            $hasStartedOnly = $validLaps === [] && $startTimestampMs > 0;

            if ($validLaps === [] && ! $hasStartedOnly) {
                continue;
            }

            if ($hasStartedOnly) {
                $pData = $item['participantData'];
                $processedParticipants[] = [
                    'participantData' => $pData,
                    'last_lap_number' => 0,
                    'total_race_time_ms' => (int) ($item['totalRaceTimeMs'] ?? 0),
                    'last_lap_timestamp_ms' => (int) ($item['lastLapTimestampMs'] ?? 0),
                    'best_lap_time_ms' => null,
                    'last_lap_time_ms' => null,
                    'reference_best_lap_time_ms' => null,
                    'has_started_only' => true,
                ];

                continue;
            }

            $lapTimes = array_map(static fn (array $lap): int => (int) $lap['lapTimeMs'], $validLaps);
            $bestLapTimeMs = min($lapTimes);
            $lastLapTimeMs = (int) end($validLaps)['lapTimeMs'];
            $previousLapTimes = count($validLaps) > 1
                ? array_map(
                    static fn (array $lap): int => (int) $lap['lapTimeMs'],
                    array_slice($validLaps, 0, -1),
                )
                : [];
            $referenceBestLapTimeMs = $previousLapTimes !== []
                ? min($previousLapTimes)
                : $lastLapTimeMs;
            $lastLapNumber = (int) ($item['lapCount'] ?? count($validLaps));

            $processedParticipants[] = [
                'participantData' => $item['participantData'],
                'last_lap_number' => $lastLapNumber,
                'total_race_time_ms' => (int) ($item['totalRaceTimeMs'] ?? 0),
                'last_lap_timestamp_ms' => (int) ($item['lastLapTimestampMs'] ?? 0),
                'best_lap_time_ms' => $bestLapTimeMs,
                'last_lap_time_ms' => $lastLapTimeMs,
                'reference_best_lap_time_ms' => $referenceBestLapTimeMs,
            ];

            if ($lastLapNumber > $overallMaxLapNumber) {
                $overallMaxLapNumber = $lastLapNumber;
            }
        }

        if ($processedParticipants === []) {
            return ['last_lap_number' => 0, 'participants' => []];
        }

        $bestLapTimes = array_values(array_filter(
            array_column($processedParticipants, 'best_lap_time_ms'),
            static fn ($value): bool => $value !== null && (int) $value > 0,
        ));
        $leaderBestLapTimeMs = $bestLapTimes !== [] ? min($bestLapTimes) : 0;

        $finalParticipants = [];
        foreach ($processedParticipants as $participant) {
            $pData = $participant['participantData'];

            if (! empty($participant['has_started_only'])) {
                $finalParticipants[] = [
                    'id' => $pData['id'] ?? null,
                    'lapCount' => 0,
                    'lastLapTimestampMs' => $participant['last_lap_timestamp_ms'],
                    'totalRaceTimeMs' => $participant['total_race_time_ms'],
                    'position' => 0,
                    'displayTimeMs' => 0,
                    'lastLapDeltaSec' => 0.0,
                    'laps_behind' => 0,
                    'participantData' => self::participantData($pData),
                ];

                continue;
            }

            $lastLapDeltaMs = $participant['last_lap_time_ms'] - $participant['reference_best_lap_time_ms'];

            $finalParticipants[] = [
                'id' => $pData['id'] ?? null,
                'lapCount' => $participant['last_lap_number'],
                'lastLapTimestampMs' => $participant['last_lap_timestamp_ms'],
                'totalRaceTimeMs' => $participant['total_race_time_ms'],
                'bestLapTimeMs' => $participant['best_lap_time_ms'],
                'position' => 0,
                'displayTimeMs' => $participant['best_lap_time_ms'] - $leaderBestLapTimeMs,
                'lastLapDeltaSec' => round($lastLapDeltaMs / 1000, 3),
                'laps_behind' => 0,
                'participantData' => self::participantData($pData),
            ];
        }

        usort($finalParticipants, static function ($a, $b): int {
            $aHasBest = array_key_exists('bestLapTimeMs', $a) && (int) ($a['bestLapTimeMs'] ?? 0) > 0;
            $bHasBest = array_key_exists('bestLapTimeMs', $b) && (int) ($b['bestLapTimeMs'] ?? 0) > 0;

            if ($aHasBest && ! $bHasBest) {
                return -1;
            }

            if (! $aHasBest && $bHasBest) {
                return 1;
            }

            if (! $aHasBest && ! $bHasBest) {
                return $a['lastLapTimestampMs'] <=> $b['lastLapTimestampMs'];
            }

            $bestDiff = $a['bestLapTimeMs'] <=> $b['bestLapTimeMs'];
            if ($bestDiff !== 0) {
                return $bestDiff;
            }

            return $a['lastLapTimestampMs'] <=> $b['lastLapTimestampMs'];
        });

        foreach ($finalParticipants as $idx => &$p) {
            $p['position'] = $idx + 1;
        }
        unset($p);

        return [
            'last_lap_number' => $overallMaxLapNumber,
            'participants' => $finalParticipants,
        ];
    }

    /**
     * @param  array<string, mixed>  $pData
     * @return array<string, mixed>
     */
    private static function participantData(array $pData): array
    {
        return [
            'id' => $pData['id'] ?? null,
            'name' => $pData['name'] ?? '',
            'surname' => $pData['surname'] ?? '',
            'patronymic' => $pData['patronymic'] ?? '',
            'start_number' => $pData['start_number'] ?? null,
        ];
    }
}
