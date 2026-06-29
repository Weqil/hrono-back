<?php

namespace App\Support;

use App\Models\Arrival;
use App\Models\ArrivalResult;

final class ArrivalFinalResultsPresenter
{
    public static function canPresent(Arrival $arrival): bool
    {
        return $arrival->finished_at !== null && $arrival->results->isNotEmpty();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function present(Arrival $arrival): ?array
    {
        if (! self::canPresent($arrival)) {
            return null;
        }

        /** @var ArrivalResult $firstResult */
        $firstResult = $arrival->results->first();

        return [
            'server_arrival_id' => $arrival->id,
            'server_race_id' => $firstResult->server_race_id,
            'local_arrival_id' => $arrival->local_arrival_id,
            'finished_at_ms' => $arrival->finished_at->getTimestampMs(),
            'name' => $arrival->name,
            'time' => $arrival->time,
            'results' => $arrival->results->map(static function (ArrivalResult $result): array {
                return [
                    'place' => $result->place,
                    'total_laps' => $result->total_laps,
                    'total_time_ms' => $result->total_time_ms,
                    'best_lap_time_ms' => $result->best_lap_time_ms,
                    'user' => [
                        'id' => $result->user_id,
                        'name' => $result->name,
                        'surname' => $result->surname,
                        'patronymic' => $result->patronymic,
                        'start_number' => $result->start_number,
                        'tag_id' => $result->tag_id,
                        'grade' => $result->grade,
                        'command' => $result->command,
                    ],
                    'laps' => $result->laps->map(static fn ($lap): array => [
                        'lap_number' => $lap->lap_number,
                        'lap_time_ms' => $lap->lap_time_ms,
                        'timestamp_ms' => $lap->timestamp_ms,
                        'position_on_lap' => $lap->position_on_lap,
                        'is_manual' => $lap->is_manual,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }
}
