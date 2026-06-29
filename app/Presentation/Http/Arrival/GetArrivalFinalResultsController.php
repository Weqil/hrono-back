<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\GetArrivalFinalResultsAction;
use App\Application\Arrival\Enums\GetArrivalFinalResultsOutcome;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\JsonResponse;

final class GetArrivalFinalResultsController extends Controller
{
    public function __invoke(string $id, GetArrivalFinalResultsAction $action): JsonResponse
    {
        $result = $action->execute($id);

        return match ($result['outcome']) {
            GetArrivalFinalResultsOutcome::Found => ApiJsonResponse::success(
                __('arrivals.final_results_fetched'),
                200,
                $result['data'],
            ),
            GetArrivalFinalResultsOutcome::ArrivalNotFound => abort(404, __('arrivals.not_found')),
            GetArrivalFinalResultsOutcome::FinalResultsNotFound => abort(404, __('arrivals.final_results_not_found')),
        };
    }
}
