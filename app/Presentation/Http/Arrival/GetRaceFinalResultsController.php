<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\GetRaceFinalResultsAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\JsonResponse;

final class GetRaceFinalResultsController extends Controller
{
    public function __invoke(string $id, GetRaceFinalResultsAction $action): JsonResponse
    {
        return ApiJsonResponse::success(
            __('arrivals.race_final_results_fetched'),
            200,
            $action->execute((int) $id),
        );
    }
}
