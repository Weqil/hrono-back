<?php

namespace App\Application\Arrival\Actions;

use App\Models\Arrival;
use App\Support\LiveResultsToFinalResultsMapper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Persists final results from the last live snapshot (auto-finish path).
 * No-op when finals already exist or the snapshot is empty.
 */
final class PersistArrivalFinalResultsFromLiveSnapshotAction
{
    public function execute(Arrival $arrival, ?Carbon $finishedAt = null): bool
    {
        if ($arrival->hasFinalResults()) {
            return false;
        }

        $items = $arrival->last_live_results;
        if (! is_array($items) || $items === []) {
            return false;
        }

        $arrival->loadMissing('arrivalType');
        $rows = LiveResultsToFinalResultsMapper::map($arrival, $items);

        if ($rows === []) {
            return false;
        }

        $finishedAt ??= now();
        $serverRaceId = (int) $arrival->moto_race_id;

        DB::transaction(function () use ($arrival, $rows, $finishedAt, $serverRaceId): void {
            $arrival->results()->delete();

            $arrival->forceFill([
                'finished' => true,
                'finished_at' => $finishedAt,
            ])->save();

            foreach ($rows as $resultRow) {
                $user = $resultRow['user'];

                $result = $arrival->results()->create([
                    'server_race_id' => $serverRaceId,
                    'place' => (int) $resultRow['place'],
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

        Log::channel('info')->info('arrivals.final_results.auto_saved', [
            'arrival_id' => $arrival->getKey(),
            'moto_race_id' => $arrival->moto_race_id,
            'results_count' => count($rows),
        ]);

        return true;
    }
}
