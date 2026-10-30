<?php

namespace Tests\Unit;

use App\Models\Arrival;
use App\Models\ArrivalType;
use App\Support\LiveResultsToFinalResultsMapper;
use Database\Seeders\ArrivalTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LiveResultsToFinalResultsMapperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ArrivalTypeSeeder::class);
    }

    public function test_it_ranks_regular_by_laps_then_time(): void
    {
        $regularTypeId = ArrivalType::query()->where('slug', 'regular')->value('id');

        $arrival = Arrival::query()->create([
            'name' => 'A',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 1,
            'arrival_type_id' => $regularTypeId,
        ]);

        $rows = LiveResultsToFinalResultsMapper::map($arrival, [
            [
                'id' => 2,
                'lapCount' => 1,
                'totalRaceTimeMs' => 60000,
                'participantData' => ['id' => 2, 'name' => 'B', 'surname' => 'B', 'start_number' => 2],
                'laps' => [
                    ['lapNumber' => 1, 'lapTimeMs' => 60000, 'timestampMs' => 60000, 'isManual' => false],
                ],
            ],
            [
                'id' => 1,
                'lapCount' => 2,
                'totalRaceTimeMs' => 110000,
                'participantData' => ['id' => 1, 'name' => 'A', 'surname' => 'A', 'start_number' => 1],
                'laps' => [
                    ['lapNumber' => 1, 'lapTimeMs' => 55000, 'timestampMs' => 55000, 'isManual' => false],
                    ['lapNumber' => 2, 'lapTimeMs' => 55000, 'timestampMs' => 110000, 'isManual' => false],
                ],
            ],
        ]);

        $this->assertCount(2, $rows);
        $this->assertSame(1, $rows[0]['place']);
        $this->assertSame(1, $rows[0]['user']['id']);
        $this->assertSame(2, $rows[0]['total_laps']);
        $this->assertSame(55000, $rows[0]['best_lap_time_ms']);
        $this->assertSame(2, $rows[1]['user']['id']);
    }
}
