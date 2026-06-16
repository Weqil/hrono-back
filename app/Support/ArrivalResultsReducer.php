<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

final class ArrivalResultsReducer
{
    /**
     * @param  array<int, mixed>  $items  Массив участников из check-point, структура IParticipantState
     * @return array{last_lap_number:int, participants:array<int, mixed>} Reduced & sorted payload parts for Moto.
     */
    public static function reduce(array $items): array
    {
        // Log::debug('--- ArrivalResultsReducer START ---', ['all_items' => $items]);

        $processedParticipants = [];
        $overallMaxLapNumber = 0;

        // 1. ПЕРВИЧНАЯ ОБРАБОТКА
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['participantData']) || !is_array($item['participantData'])) {
                continue;
            }

            $laps = $item['laps'] ?? [];
            $lapTimes = array_column(array_filter($laps, fn($lap) => ($lap['lapTimeMs'] ?? 0) > 0), 'lapTimeMs');
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

        // 2. ОПРЕДЕЛЕНИЕ ЛИДЕРА
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

        // 3. РАСЧЕТ ОТСТАВАНИЙ И ФОРМИРОВАНИЕ ВЫХОДНОГО МАССИВА
        $finalParticipants = [];
        foreach ($processedParticipants as $participant) {
            $lapDiff = $leader['last_lap_number'] - $participant['last_lap_number'];

            $displayTimeMs = 0;
            // Рассчитываем отставание в мс, только если участник на том же круге, что и лидер
            if ($lapDiff === 0) {
                $displayTimeMs = $participant['total_race_time_ms'] - $leader['total_race_time_ms'];
            }

            $pData = $participant['participantData'];
            $finalParticipants[] = [
                'id' => $pData['id'] ?? null,
                'lapCount' => $participant['last_lap_number'],
                'lastLapTimestampMs' => $participant['last_lap_timestamp_ms'],
                'totalRaceTimeMs' => $participant['total_race_time_ms'],
                'position' => 0, // Будет присвоено после сортировки
                'displayTimeMs' => $displayTimeMs, // Отставание в мс (только для тех, кто на круге лидера)
                'laps_behind' => $lapDiff, // Количество кругов отставания (0, 1, 2...)
                'participantData' => [
                    'id' => $pData['id'] ?? null,
                    'name' => $pData['name'] ?? '',
                    'surname' => $pData['surname'] ?? '',
                    'patronymic' => $pData['patronymic'] ?? '',
                    'start_number' => $pData['start_number'] ?? null,
                ],
            ];
        }

        // 4. СОРТИРОВКА И ПРИСВОЕНИЕ МЕСТ
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

        $filteredFinalParticipants = array_filter($finalParticipants, function ($finalParticipant) {
            return $finalParticipant['lapCount'] > 0;
        });

        return [
            'last_lap_number' => $overallMaxLapNumber,
            'participants' => $filteredFinalParticipants,
        ];
    }
}
