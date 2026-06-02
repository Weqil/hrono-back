<?php

namespace App\Support;

final class ArrivalResultsReducer
{
    /**
     * @param  array<int, mixed>  $items
     * @return array{last_lap_number:int, participants:array<int, mixed>} Reduced & sorted payload parts for Moto.
     */
    public static function reduce(array $items): array
    {
        // Find latest lap number across all riders.
        $maxLapNumber = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $laps = $item['laps'] ?? null;
            if (! is_array($laps)) {
                continue;
            }

            foreach ($laps as $lap) {
                if (! is_array($lap)) {
                    continue;
                }

                $lapNumber = $lap['lapNumber'] ?? null;
                if (is_int($lapNumber) && $lapNumber > $maxLapNumber) {
                    $maxLapNumber = $lapNumber;
                } elseif (is_string($lapNumber) && ctype_digit($lapNumber)) {
                    $n = (int) $lapNumber;
                    if ($n > $maxLapNumber) {
                        $maxLapNumber = $n;
                    }
                }
            }
        }

        if ($maxLapNumber <= 0) {
            return [
                'last_lap_number' => 0,
                'participants' => [],
            ];
        }

        // Filter riders who have the latest lap.
        $filtered = array_values(array_filter($items, static function ($item) use ($maxLapNumber): bool {
            if (! is_array($item)) {
                return false;
            }

            $laps = $item['laps'] ?? null;
            if (! is_array($laps)) {
                return false;
            }

            foreach ($laps as $lap) {
                if (! is_array($lap)) {
                    continue;
                }

                $lapNumber = $lap['lapNumber'] ?? null;
                if ($lapNumber === $maxLapNumber) {
                    return true;
                }
                if (is_string($lapNumber) && ctype_digit($lapNumber) && (int) $lapNumber === $maxLapNumber) {
                    return true;
                }
            }

            return false;
        }));

        // Sort by lastLapTimestampMs ascending.
        usort($filtered, static function ($a, $b): int {
            $aTs = is_array($a) ? ($a['lastLapTimestampMs'] ?? null) : null;
            $bTs = is_array($b) ? ($b['lastLapTimestampMs'] ?? null) : null;

            $aNum = is_int($aTs) || is_float($aTs)
                ? (float) $aTs
                : (is_string($aTs) && is_numeric($aTs) ? (float) $aTs : INF);

            $bNum = is_int($bTs) || is_float($bTs)
                ? (float) $bTs
                : (is_string($bTs) && is_numeric($bTs) ? (float) $bTs : INF);

            return $aNum <=> $bNum;
        });

        $leaderTs = null;
        if (isset($filtered[0]) && is_array($filtered[0])) {
            $ts = $filtered[0]['lastLapTimestampMs'] ?? null;
            if (is_int($ts) || is_float($ts) || (is_string($ts) && is_numeric($ts))) {
                $leaderTs = (float) $ts;
            }
        }

        foreach ($filtered as $idx => &$item) {
            if (! is_array($item)) {
                continue;
            }

            unset($item['laps']);
            $item['position'] = $idx + 1;

            $ts = $item['lastLapTimestampMs'] ?? null;
            if ($leaderTs !== null && (is_int($ts) || is_float($ts) || (is_string($ts) && is_numeric($ts)))) {
                $item['displayTimeMs'] = (float) $ts - $leaderTs;
            } else {
                $item['displayTimeMs'] = null;
            }
        }
        unset($item);

        return [
            'last_lap_number' => $maxLapNumber,
            'participants' => $filtered,
        ];
    }
}

