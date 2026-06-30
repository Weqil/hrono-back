<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\GetArrivalTypesAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\JsonResponse;

final class GetArrivalTypesController extends Controller
{
    public function __invoke(GetArrivalTypesAction $action): JsonResponse
    {
        return ApiJsonResponse::success(
            __('arrivals.types_fetched'),
            200,
            [
                'types' => $action->execute(),
            ],
        );
    }
}
