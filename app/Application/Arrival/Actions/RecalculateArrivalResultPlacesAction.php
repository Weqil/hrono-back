<?php

namespace App\Application\Arrival\Actions;

use App\Models\Arrival;
use App\Models\ArrivalResult;

final class RecalculateArrivalResultPlacesAction
{
    public function __invoke(int $arrivalId): void
    {
        $arrival = Arrival::query()->with('arrivalType')->find($arrivalId);

        if ($arrival === null || $arrival->isQualification()) {
            return;
        }

        $results = ArrivalResult::query()
            ->where('arrival_id', $arrivalId)
            ->orderBy('total_time_ms')
            ->orderBy('id')
            ->get();

        $place = 1;

        foreach ($results as $result) {
            if ($result->place !== $place) {
                $result->update(['place' => $place]);
            }

            $place++;
        }
    }
}
