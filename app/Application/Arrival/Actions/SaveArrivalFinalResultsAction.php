<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\CloseArrivalStreamOutcome;
use App\Application\Arrival\Enums\SaveArrivalFinalResultsOutcome;
use App\Models\Arrival;
use App\Support\MotoBearerExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SaveArrivalFinalResultsAction
{
    public function __construct(
        private readonly CloseArrivalStreamAction $closeArrivalStream,
    ) {}

    public function execute(string $arrivalId, Request $request): SaveArrivalFinalResultsOutcome
    {
        $data = $request->validate([
            'server_arrival_id' => ['required', 'integer', 'min:1'],
            'server_race_id' => ['required', 'integer', 'min:1'],
            'local_arrival_id' => ['required', 'integer', 'min:1'],
            'finished_at_ms' => ['required', 'numeric', 'min:0'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.place' => ['nullable', 'numeric', 'min:1'],
            'results.*.total_laps' => ['required', 'numeric', 'min:0'],
            'results.*.total_time_ms' => ['required', 'numeric', 'min:0'],
            'results.*.best_lap_time_ms' => ['nullable', 'numeric', 'min:0'],
            'results.*.user' => ['required', 'array'],
            'results.*.user.id' => ['required', 'integer', 'min:1'],
            'results.*.user.name' => ['required', 'string', 'max:255'],
            'results.*.user.surname' => ['nullable', 'string', 'max:255'],
            'results.*.user.patronymic' => ['nullable', 'string', 'max:255'],
            'results.*.user.start_number' => ['required', 'numeric', 'min:0'],
            'results.*.user.tag_id' => ['nullable', 'string', 'max:255'],
            'results.*.user.grade' => ['nullable', 'string', 'max:255'],
            'results.*.user.command' => ['nullable', 'string', 'max:255'],
            'results.*.laps' => ['present', 'array'],
            'results.*.laps.*.lap_number' => ['required', 'integer', 'min:1'],
            'results.*.laps.*.lap_time_ms' => ['required', 'numeric', 'min:0'],
            'results.*.laps.*.timestamp_ms' => ['required', 'numeric', 'min:0'],
            'results.*.laps.*.position_on_lap' => ['nullable', 'numeric', 'min:1'],
            'results.*.laps.*.is_manual' => ['required', 'boolean'],
        ]);

        if ((int) $arrivalId !== (int) $data['server_arrival_id']) {
            return SaveArrivalFinalResultsOutcome::ServerArrivalIdMismatch;
        }

        $arrival = Arrival::query()->find($arrivalId);

        if ($arrival === null) {
            return SaveArrivalFinalResultsOutcome::ArrivalNotFound;
        }

        if ((int) $arrival->moto_race_id !== (int) $data['server_race_id']) {
            return SaveArrivalFinalResultsOutcome::RaceIdMismatch;
        }

        $shouldCloseStream = $arrival->canCloseMotoStream();

        if ($shouldCloseStream && MotoBearerExtractor::fromRequest($request) === null) {
            return SaveArrivalFinalResultsOutcome::StreamBearerMissing;
        }

        DB::transaction(function () use ($arrival, $data): void {
            $arrival->results()->delete();

            $arrival->forceFill([
                'local_arrival_id' => (int) $data['local_arrival_id'],
                'finished' => true,
                'finished_at' => Carbon::createFromTimestampMs((int) $data['finished_at_ms']),
            ])->save();

            foreach ($data['results'] as $resultRow) {
                $user = $resultRow['user'];

                $result = $arrival->results()->create([
                    'server_race_id' => (int) $data['server_race_id'],
                    'place' => (int) ($resultRow['place'] ?? 0),
                    'total_laps' => (int) $resultRow['total_laps'],
                    'total_time_ms' => (int) $resultRow['total_time_ms'],
                    'best_lap_time_ms' => (int) ($resultRow['best_lap_time_ms'] ?? 0),
                    'user_id' => (int) $user['id'],
                    'name' => $user['name'],
                    'surname' => $user['surname'] ?? '',
                    'patronymic' => $user['patronymic'] ?? '',
                    'start_number' => (int) $user['start_number'],
                    'tag_id' => $user['tag_id'] ?? '',
                    'grade' => $user['grade'] ?? '',
                    'command' => $user['command'] ?? '',
                ]);

                foreach ($resultRow['laps'] as $lapRow) {
                    $result->laps()->create([
                        'lap_number' => (int) $lapRow['lap_number'],
                        'lap_time_ms' => (int) $lapRow['lap_time_ms'],
                        'timestamp_ms' => (int) $lapRow['timestamp_ms'],
                        'position_on_lap' => (int) ($lapRow['position_on_lap'] ?? 0),
                        'is_manual' => filter_var($lapRow['is_manual'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }
        });

        if (! $shouldCloseStream) {
            return SaveArrivalFinalResultsOutcome::Saved;
        }

        return match ($this->closeArrivalStream->execute($arrivalId, $request)) {
            CloseArrivalStreamOutcome::Closed => SaveArrivalFinalResultsOutcome::Saved,
            CloseArrivalStreamOutcome::MotoFailed => SaveArrivalFinalResultsOutcome::StreamCloseFailed,
            default => SaveArrivalFinalResultsOutcome::StreamCloseFailed,
        };
    }
}
