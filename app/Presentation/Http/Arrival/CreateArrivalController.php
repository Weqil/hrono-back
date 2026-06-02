<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\CreateArrivalAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreateArrivalController extends Controller
{
    public function __invoke(Request $request, CreateArrivalAction $action): JsonResponse
    {
        $arrival = $action->execute($request);

        return ApiJsonResponse::success(
            __('arrivals.created'),
            201,
            ['id' => (string) $arrival->getKey()],
        );
    }
}
