<?php

namespace App\Application\Arrival\Actions;

use App\Models\Arrival;
use App\Models\ArrivalResult;

final class RecalculateArrivalResultPlacesByBestLapAction
{
    public function __invoke(int $arrivalId): void
    {
        $arrival = Arrival::query()->find($arrivalId);

        if ($arrival === null) {
            return;
        }

        $results = ArrivalResult::query()
            ->where('arrival_id', $arrivalId)
            ->with('laps')
            ->get();

        foreach ($results as $result) {
            $result->best_lap_time_ms = self::bestLapTimeMs($result);
            $result->save();
        }

        $ordered = $results
            ->sort(static function (ArrivalResult $a, ArrivalResult $b): int {
                return [self::sortableBestLap($a), $a->getKey()]
                    <=> [self::sortableBestLap($b), $b->getKey()];
            })
            ->values();

        $place = 1;

        foreach ($ordered as $result) {
            if ($result->place !== $place) {
                $result->update(['place' => $place]);
            }

            $place++;
        }
    }

    /**
     * Лучший круг без учёта первого круга (lap_number = 1).
     */
    private static function bestLapTimeMs(ArrivalResult $result): int
    {
        return (int) $result->laps
            ->where('lap_number', '>', 1)
            ->where('lap_time_ms', '>', 0)
            ->min('lap_time_ms');
    }

    private static function sortableBestLap(ArrivalResult $result): int
    {
        $bestLapTimeMs = (int) $result->best_lap_time_ms;

        return $bestLapTimeMs > 0 ? $bestLapTimeMs : PHP_INT_MAX;
    }
}
