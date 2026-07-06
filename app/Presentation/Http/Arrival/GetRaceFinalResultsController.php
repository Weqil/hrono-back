<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\GetRaceFinalResultsAction;
use App\Application\Arrival\Enums\ArrivalKind;
use App\Application\Arrival\Support\ArrivalTypeResolver;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class GetRaceFinalResultsController extends Controller
{
    public function __invoke(string $id, Request $request, GetRaceFinalResultsAction $action): JsonResponse
    {
        $pathFilter = $request->route('arrivalTypeFilter');

        if (is_string($pathFilter) && $pathFilter !== '') {
            $arrivalTypeId = ArrivalTypeResolver::resolvePathFilter($pathFilter);
        } else {
            $filters = $request->validate([
                'arrival_type' => ['sometimes', 'nullable', 'string', Rule::enum(ArrivalKind::class)],
                'arrival_type_id' => ['sometimes', 'nullable', 'integer', 'exists:arrival_types,id'],
            ]);

            $arrivalTypeId = ArrivalTypeResolver::resolveId(
                $filters['arrival_type'] ?? null,
                filled($filters['arrival_type_id'] ?? null) ? (int) $filters['arrival_type_id'] : null,
            );
        }

        return ApiJsonResponse::success(
            __('arrivals.race_final_results_fetched'),
            200,
            $action->execute((int) $id, $arrivalTypeId),
        );
    }
}
