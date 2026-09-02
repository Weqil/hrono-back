<?php

namespace App\Application\Arrival\Actions;

use App\Models\Arrival;
use App\Models\ArrivalResult;
use App\Support\QualificationBestLap;

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
     * Лучший круг с учётом ручных отметок и круга сразу после них.
     */
    private static function bestLapTimeMs(ArrivalResult $result): int
    {
        $laps = $result->laps
            ->sortBy('lap_number')
            ->map(static fn ($lap): array => [
                'lap_number' => $lap->lap_number,
                'lap_time_ms' => $lap->lap_time_ms,
                'timestamp_ms' => $lap->timestamp_ms,
                'is_manual' => $lap->is_manual,
            ])
            ->values()
            ->all();

        return QualificationBestLap::getBestLapTimeMs($laps) ?? 0;
    }

    private static function sortableBestLap(ArrivalResult $result): int
    {
        $bestLapTimeMs = (int) $result->best_lap_time_ms;

        return $bestLapTimeMs > 0 ? $bestLapTimeMs : PHP_INT_MAX;
    }
}
