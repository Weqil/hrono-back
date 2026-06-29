<?php

namespace Tests\Feature;

use App\Models\Arrival;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetRaceFinalResultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hrono.api_secret' => 'test-secret']);
    }

    public function test_it_returns_final_results_grouped_by_arrivals_sorted_by_finished_at(): void
    {
        $raceId = 156;

        $firstArrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => $raceId,
        ]);

        $secondArrival = Arrival::query()->create([
            'name' => 'Заезд 2',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '11:00',
            'arrival_grades' => [],
            'moto_race_id' => $raceId,
        ]);

        $this->postJson(
            "/arrivals/{$secondArrival->id}/final-results",
            $this->payload($secondArrival->id, $raceId, 102, 1719158500000, 'Второй'),
            ['X-Api-Secret' => 'test-secret'],
        )->assertOk();

        $this->postJson(
            "/arrivals/{$firstArrival->id}/final-results",
            $this->payload($firstArrival->id, $raceId, 101, 1719158400000, 'Первый'),
            ['X-Api-Secret' => 'test-secret'],
        )->assertOk();

        $response = $this->getJson(
            "/races/{$raceId}/final-results",
            ['X-Api-Secret' => 'test-secret'],
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.server_race_id', $raceId)
            ->assertJsonCount(2, 'data.arrivals')
            ->assertJsonPath('data.arrivals.0.server_arrival_id', $firstArrival->id)
            ->assertJsonPath('data.arrivals.0.name', 'Заезд 1')
            ->assertJsonPath('data.arrivals.0.results.0.user.name', 'Первый')
            ->assertJsonPath('data.arrivals.1.server_arrival_id', $secondArrival->id)
            ->assertJsonPath('data.arrivals.1.name', 'Заезд 2')
            ->assertJsonPath('data.arrivals.1.results.0.user.name', 'Второй');
    }

    public function test_it_returns_empty_arrivals_when_race_has_no_final_results(): void
    {
        Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);

        $this->getJson(
            '/races/156/final-results',
            ['X-Api-Secret' => 'test-secret'],
        )
            ->assertOk()
            ->assertJsonPath('data.server_race_id', 156)
            ->assertJsonPath('data.arrivals', []);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $arrivalId, int $raceId, int $localArrivalId, int $finishedAtMs, string $name): array
    {
        return [
            'server_arrival_id' => $arrivalId,
            'server_race_id' => $raceId,
            'local_arrival_id' => $localArrivalId,
            'finished_at_ms' => $finishedAtMs,
            'results' => [
                [
                    'place' => 1,
                    'total_laps' => 1,
                    'total_time_ms' => 1000,
                    'best_lap_time_ms' => 1000,
                    'user' => [
                        'id' => 1,
                        'name' => $name,
                        'surname' => '',
                        'patronymic' => '',
                        'start_number' => 1,
                        'tag_id' => '',
                        'grade' => '',
                        'command' => '',
                    ],
                    'laps' => [
                        [
                            'lap_number' => 1,
                            'lap_time_ms' => 1000,
                            'timestamp_ms' => 1000,
                            'position_on_lap' => 1,
                            'is_manual' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
