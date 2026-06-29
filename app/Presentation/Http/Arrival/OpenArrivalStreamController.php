<?php

namespace App\Presentation\Http\Arrival;

use App\Application\Arrival\Actions\OpenArrivalStreamAction;
use App\Application\Arrival\Enums\OpenArrivalStreamOutcome;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJsonResponse;
use App\Models\Arrival;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OpenArrivalStreamController extends Controller
{
    public function __invoke(Request $request, string $id, OpenArrivalStreamAction $action): JsonResponse
    {
        return match ($action->execute($id, $request)) {
            OpenArrivalStreamOutcome::Opened => ApiJsonResponse::success(
                __('arrivals.stream_opened'),
                200,
                $this->streamData($id),
            ),
            OpenArrivalStreamOutcome::ArrivalNotFound => abort(404, __('arrivals.not_found')),
            OpenArrivalStreamOutcome::BearerMissing => abort(422, __('arrivals.stream_bearer_missing')),
            OpenArrivalStreamOutcome::MotoFailed => abort(502, __('arrivals.stream_open_failed')),
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
