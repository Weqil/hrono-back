<?php

namespace App\Application\Arrival\Actions;

use App\Models\Arrival;
use App\Support\ArrivalFinalResultsPresenter;

final class GetRaceFinalResultsAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(int $raceId): array
    {
        $arrivals = Arrival::query()
            ->where('moto_race_id', $raceId)
            ->whereNotNull('finished_at')
            ->whereHas('results')
            ->with([
                'arrivalType',
                'results' => static fn ($query) => $query->orderBy('place'),
                'results.laps' => static fn ($query) => $query->orderBy('lap_number'),
            ])
            ->orderBy('finished_at')
            ->orderBy('id')
            ->get();

        $presentedArrivals = $arrivals
            ->map(static fn (Arrival $arrival) => ArrivalFinalResultsPresenter::present($arrival))
            ->filter()
            ->values()
            ->all();

        return [
            'server_race_id' => $raceId,
            'arrivals' => $presentedArrivals,
        ];
    }
}
