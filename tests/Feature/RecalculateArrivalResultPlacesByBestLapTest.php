<?php

namespace Tests\Feature;

use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesByBestLapAction;
use App\Models\Arrival;
use App\Models\ArrivalResult;
use App\Models\ArrivalResultLap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateArrivalResultPlacesByBestLapTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reassigns_places_by_best_lap_ignoring_first_lap(): void
    {
        $arrival = $this->makeArrival();

        // Первый круг быстрый (30с), но игнорируется. Лучший из остальных — 50с.
        $riderA = $this->makeResult($arrival, name: 'A', startNumber: 1, laps: [
            1 => 30_000,
            2 => 50_000,
            3 => 55_000,
        ]);

        // Первый круг медленный, лучший из остальных — 48с.
        $riderB = $this->makeResult($arrival, name: 'B', startNumber: 2, laps: [
            1 => 90_000,
            2 => 48_000,
            3 => 49_000,
        ]);

        app(RecalculateArrivalResultPlacesByBestLapAction::class)($arrival->id);

        $this->assertSame(50_000, $riderA->fresh()->best_lap_time_ms);
        $this->assertSame(48_000, $riderB->fresh()->best_lap_time_ms);

        $this->assertSame(1, $riderB->fresh()->place);
        $this->assertSame(2, $riderA->fresh()->place);
    }

    public function test_it_places_results_without_scoring_laps_last(): void
    {
        $arrival = $this->makeArrival();

        $withBestLap = $this->makeResult($arrival, name: 'A', startNumber: 1, laps: [
            1 => 30_000,
            2 => 52_000,
        ]);

        // Только первый круг — засчитываемых кругов нет.
        $onlyFirstLap = $this->makeResult($arrival, name: 'B', startNumber: 2, laps: [
            1 => 40_000,
        ]);

        app(RecalculateArrivalResultPlacesByBestLapAction::class)($arrival->id);

        $this->assertSame(52_000, $withBestLap->fresh()->best_lap_time_ms);
        $this->assertSame(0, $onlyFirstLap->fresh()->best_lap_time_ms);

        $this->assertSame(1, $withBestLap->fresh()->place);
        $this->assertSame(2, $onlyFirstLap->fresh()->place);
    }

    private function makeArrival(): Arrival
    {
        return Arrival::query()->create([
            'name' => 'Test arrival',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 1,
        ]);
    }

    /**
     * @param  array<int, int>  $laps  lap_number => lap_time_ms
     */
    private function makeResult(Arrival $arrival, string $name, int $startNumber, array $laps): ArrivalResult
    {
        $result = ArrivalResult::query()->create([
            'arrival_id' => $arrival->id,
            'server_race_id' => 1,
            'place' => $startNumber,
            'total_laps' => count($laps),
            'total_time_ms' => array_sum($laps),
            'best_lap_time_ms' => 0,
            'user_id' => $startNumber,
            'name' => $name,
            'surname' => 'Rider',
            'patronymic' => '',
            'start_number' => $startNumber,
            'tag_id' => '',
            'grade' => '',
            'command' => '',
        ]);

        $timestamp = 0;

        foreach ($laps as $lapNumber => $lapTimeMs) {
            $timestamp += $lapTimeMs;

            ArrivalResultLap::query()->create([
                'arrival_result_id' => $result->id,
                'lap_number' => $lapNumber,
                'lap_time_ms' => $lapTimeMs,
                'timestamp_ms' => $timestamp,
                'position_on_lap' => 1,
                'is_manual' => false,
            ]);
        }

        return $result;
    }
}
