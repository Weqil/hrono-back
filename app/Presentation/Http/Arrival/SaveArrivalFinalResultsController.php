<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\SaveArrivalFinalResultsAction;
use App\Application\Arrival\Enums\SaveArrivalFinalResultsOutcome;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaveArrivalFinalResultsController extends Controller
{
    public function __invoke(Request $request, string $id, SaveArrivalFinalResultsAction $action): JsonResponse
    {
        return match ($action->execute($id, $request)) {
            SaveArrivalFinalResultsOutcome::Saved => ApiJsonResponse::success(
                __('arrivals.final_results_saved'),
                200,
                ['id' => $id],
            ),
            SaveArrivalFinalResultsOutcome::ArrivalNotFound => abort(404, __('arrivals.not_found')),
            SaveArrivalFinalResultsOutcome::ServerArrivalIdMismatch => abort(422, __('arrivals.server_arrival_id_mismatch')),
            SaveArrivalFinalResultsOutcome::RaceIdMismatch => abort(422, __('arrivals.race_id_mismatch')),
            SaveArrivalFinalResultsOutcome::StreamBearerMissing => abort(422, __('arrivals.stream_bearer_missing')),
            SaveArrivalFinalResultsOutcome::StreamCloseFailed => abort(502, __('arrivals.stream_close_failed')),
        };
    }
}
