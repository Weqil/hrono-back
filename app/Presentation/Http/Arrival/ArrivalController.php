<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\ReceiveArrivalAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ArrivalController extends Controller
{
    public function __invoke(Request $request, string $id, ReceiveArrivalAction $action): JsonResponse
    {
        $action->execute($id, $request);

        return ApiJsonResponse::success(
            __('arrivals.results_received'),
            200,
            ['id' => $id],
        );
    }
}
