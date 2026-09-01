<?php

namespace Tests\Unit;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Support\ArrivalResultsReducer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ArrivalResultsReducerTest extends TestCase
{
    #[Test]
    public function test_regular_arrival_sorts_by_lap_count_then_total_time(): void
    {
        $items = [
            $this->participant(
                id: 1,
                lapCount: 2,
                totalRaceTimeMs: 100_000,
                laps: [['lapTimeMs' => 50_000], ['lapTimeMs' => 50_000]],
            ),
            $this->participant(
                id: 2,
                lapCount: 3,
                totalRaceTimeMs: 200_000,
                laps: [['lapTimeMs' => 60_000], ['lapTimeMs' => 70_000], ['lapTimeMs' => 70_000]],
            ),
            $this->participant(
                id: 3,
                lapCount: 2,
                totalRaceTimeMs: 90_000,
                laps: [['lapTimeMs' => 45_000], ['lapTimeMs' => 45_000]],
            ),
        ];

        $result = ArrivalResultsReducer::reduce($items, ArrivalKind::Regular);

        $this->assertSame(3, $result['last_lap_number']);
        $this->assertSame([2, 3, 1], array_column($result['participants'], 'id'));
        $this->assertSame([1, 2, 3], array_column($result['participants'], 'position'));
        $this->assertSame(['pending', 'pending', 'pending'], array_column($result['participants'], 'status'));
        $this->assertSame([null, null, null], array_column($result['participants'], 'finishElapsedMs'));
        $this->assertArrayNotHasKey('bestLapTimeMs', $result['participants'][0]);
        $this->assertArrayNotHasKey('lastLapDeltaSec', $result['participants'][0]);
    }

    #[Test]
    public function test_regular_arrival_forwards_participant_status_and_finish_elapsed(): void
    {
        $items = [
            $this->participant(
                id: 1,
                lapCount: 2,
                totalRaceTimeMs: 100_000,
                laps: [['lapTimeMs' => 50_000], ['lapTimeMs' => 50_000]],
                status: 'finished',
                finishElapsedMs: 100_000,
            ),
            $this->participant(
                id: 2,
                lapCount: 1,
                totalRaceTimeMs: 55_000,
                laps: [['lapTimeMs' => 55_000]],
                status: 'pending',
                finishElapsedMs: null,
            ),
        ];

        $result = ArrivalResultsReducer::reduce($items, ArrivalKind::Regular);

        $this->assertSame(1, $result['participants'][0]['id']);
        $this->assertSame('finished', $result['participants'][0]['status']);
        $this->assertSame(100_000, $result['participants'][0]['finishElapsedMs']);
        $this->assertSame(2, $result['participants'][1]['id']);
        $this->assertSame('pending', $result['participants'][1]['status']);
        $this->assertNull($result['participants'][1]['finishElapsedMs']);
    }

    #[Test]
    public function test_qualification_arrival_sorts_by_best_lap_time(): void
    {
        $items = [
            $this->participant(
                id: 1,
                lapCount: 2,
                totalRaceTimeMs: 102_000,
                laps: [['lapTimeMs' => 52_000], ['lapTimeMs' => 50_000]],
                lastLapTimestampMs: 102_000,
            ),
            $this->participant(
                id: 2,
                lapCount: 3,
                totalRaceTimeMs: 145_000,
                laps: [['lapTimeMs' => 48_000], ['lapTimeMs' => 49_000], ['lapTimeMs' => 48_000]],
                lastLapTimestampMs: 145_000,
            ),
            $this->participant(
                id: 3,
                lapCount: 1,
                totalRaceTimeMs: 47_500,
                laps: [['lapTimeMs' => 47_500]],
                lastLapTimestampMs: 47_500,
            ),
        ];

        $result = ArrivalResultsReducer::reduce($items, ArrivalKind::Qualification);

        $this->assertSame(3, $result['last_lap_number']);
        $this->assertSame([3, 2, 1], array_column($result['participants'], 'id'));
        $this->assertSame([1, 2, 3], array_column($result['participants'], 'position'));
        $this->assertSame(47_500, $result['participants'][0]['bestLapTimeMs']);
        $this->assertSame(47_500, $result['participants'][0]['lastLapTimeMs']);
        $this->assertSame(48_000, $result['participants'][1]['bestLapTimeMs']);
        $this->assertSame(48_000, $result['participants'][1]['lastLapTimeMs']);
        $this->assertSame(50_000, $result['participants'][2]['bestLapTimeMs']);
        $this->assertSame(50_000, $result['participants'][2]['lastLapTimeMs']);
        $this->assertSame(0, $result['participants'][0]['displayTimeMs']);
        $this->assertSame(500, $result['participants'][1]['displayTimeMs']);
        $this->assertSame(2_500, $result['participants'][2]['displayTimeMs']);
    }

    #[Test]
    public function test_qualification_arrival_includes_last_lap_delta_in_seconds(): void
    {
        $items = [
            $this->participant(
                id: 1,
                lapCount: 2,
                totalRaceTimeMs: 102_000,
                laps: [['lapTimeMs' => 50_000], ['lapTimeMs' => 52_000]],
            ),
            $this->participant(
                id: 2,
                lapCount: 2,
                totalRaceTimeMs: 98_000,
                laps: [['lapTimeMs' => 49_000], ['lapTimeMs' => 49_000]],
            ),
        ];

        $result = ArrivalResultsReducer::reduce($items, ArrivalKind::Qualification);

        $this->assertSame(0.0, $result['participants'][0]['lastLapDeltaSec']);
        $this->assertSame(2.0, $result['participants'][1]['lastLapDeltaSec']);
    }

    #[Test]
    public function test_qualification_arrival_returns_negative_last_lap_delta_when_last_lap_is_faster(): void
    {
        $items = [
            $this->participant(
                id: 1,
                lapCount: 2,
                totalRaceTimeMs: 98_000,
                laps: [['lapTimeMs' => 52_000], ['lapTimeMs' => 46_000]],
            ),
        ];

        $result = ArrivalResultsReducer::reduce($items, ArrivalKind::Qualification);

        $this->assertSame(-6.0, $result['participants'][0]['lastLapDeltaSec']);
        $this->assertSame(46_000, $result['participants'][0]['bestLapTimeMs']);
    }

    #[Test]
    public function test_qualification_arrival_includes_participant_with_start_only(): void
    {
        $items = [
            $this->participant(
                id: 1,
                lapCount: 0,
                totalRaceTimeMs: 12_000,
                laps: [],
                lastLapTimestampMs: 12_000,
                startTimestampMs: 12_000,
            ),
            $this->participant(
                id: 2,
                lapCount: 1,
                totalRaceTimeMs: 60_000,
                laps: [['lapTimeMs' => 48_000]],
                lastLapTimestampMs: 60_000,
            ),
        ];

        $result = ArrivalResultsReducer::reduce($items, ArrivalKind::Qualification);

        $this->assertCount(2, $result['participants']);
        $this->assertSame(2, $result['participants'][0]['id']);
        $this->assertSame(1, $result['participants'][1]['id']);
        $this->assertSame(0, $result['participants'][1]['lapCount']);
        $this->assertArrayNotHasKey('bestLapTimeMs', $result['participants'][1]);
    }

    /**
     * @param  array<int, array{lapTimeMs:int}>  $laps
     * @return array<string, mixed>
     */
    private function participant(
        int $id,
        int $lapCount,
        int $totalRaceTimeMs,
        array $laps,
        int $lastLapTimestampMs = 0,
        ?int $startTimestampMs = null,
        string $status = 'pending',
        ?int $finishElapsedMs = null,
    ): array {
        return [
            'participantData' => [
                'id' => $id,
                'name' => "Rider{$id}",
                'surname' => 'Test',
                'patronymic' => '',
                'start_number' => $id,
                'status' => $status,
                'finishElapsedMs' => $finishElapsedMs,
            ],
            'lapCount' => $lapCount,
            'totalRaceTimeMs' => $totalRaceTimeMs,
            'lastLapTimestampMs' => $lastLapTimestampMs,
            'startTimestampMs' => $startTimestampMs,
            'laps' => $laps,
        ];
    }
}
