<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\GetArrivalFinalResultsOutcome;
use App\Models\Arrival;
use App\Support\ArrivalFinalResultsPresenter;

final class GetArrivalFinalResultsAction
{
    /**
     * @return array{outcome: GetArrivalFinalResultsOutcome, data: array<string, mixed>|null}
     */
    public function execute(string $arrivalId): array
    {
        $arrival = Arrival::query()
            ->with([
                'arrivalType',
                'results' => static fn ($query) => $query->orderBy('place'),
                'results.laps' => static fn ($query) => $query->orderBy('lap_number'),
            ])
            ->find($arrivalId);

        if ($arrival === null) {
            return [
                'outcome' => GetArrivalFinalResultsOutcome::ArrivalNotFound,
                'data' => null,
            ];
        }

        $data = ArrivalFinalResultsPresenter::present($arrival);

        if ($data === null) {
            return [
                'outcome' => GetArrivalFinalResultsOutcome::FinalResultsNotFound,
                'data' => null,
            ];
        }

        return [
            'outcome' => GetArrivalFinalResultsOutcome::Found,
            'data' => $data,
        ];
    }
}
