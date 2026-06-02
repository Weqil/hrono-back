<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\CloseArrivalStreamAction;
use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use App\Models\Arrival;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CloseArrivalStreamController extends Controller
{
    public function __invoke(Request $request, string $id, CloseArrivalStreamAction $action): JsonResponse
    {
        return match ($action->execute($id, $request)) {
            CloseArrivalStreamOutcome::Closed => ApiJsonResponse::success(
                __('arrivals.stream_closed'),
                200,
                $this->streamData($id),
            ),
            CloseArrivalStreamOutcome::NotOpened => abort(409, __('arrivals.stream_not_opened')),
            CloseArrivalStreamOutcome::AlreadyClosed => abort(409, __('arrivals.stream_already_closed')),
            CloseArrivalStreamOutcome::ArrivalNotFound => abort(404, __('arrivals.not_found')),
            CloseArrivalStreamOutcome::BearerMissing => abort(422, __('arrivals.stream_bearer_missing')),
            CloseArrivalStreamOutcome::MotoFailed => abort(502, __('arrivals.stream_close_failed')),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function streamData(string $id): array
    {
        $arrival = Arrival::query()->find($id);

        return [
            'id' => $id,
            'moto_stream_opened_at' => $arrival?->moto_stream_opened_at?->toIso8601String(),
            'moto_stream_closed_at' => $arrival?->moto_stream_closed_at?->toIso8601String(),
        ];
    }
}
