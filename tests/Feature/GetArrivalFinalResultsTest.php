<?php

namespace Tests\Feature;

use App\Models\Arrival;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetArrivalFinalResultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hrono.api_secret' => 'test-secret']);
    }

    public function test_it_returns_saved_final_results(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => ['125 см3'],
            'moto_race_id' => 156,
        ]);

        $payload = [
            'server_arrival_id' => $arrival->id,
            'server_race_id' => 156,
            'local_arrival_id' => 101,
            'finished_at_ms' => 1719158400000,
            'results' => [
                [
                    'place' => 1,
                    'total_laps' => 2,
                    'total_time_ms' => 500000,
                    'best_lap_time_ms' => 245100,
                    'user' => [
                        'id' => 12,
                        'name' => 'Иван',
                        'surname' => 'Петров',
                        'patronymic' => 'Сергеевич',
                        'start_number' => 7,
                        'tag_id' => '',
                        'grade' => '125 см3',
                        'command' => 'Мотоклуб',
                    ],
                    'laps' => [
                        [
                            'lap_number' => 1,
                            'lap_time_ms' => 251200,
                            'timestamp_ms' => 251200,
                            'position_on_lap' => 2,
                            'is_manual' => false,
                        ],
                        [
                            'lap_number' => 2,
                            'lap_time_ms' => 248800,
                            'timestamp_ms' => 500000,
                            'position_on_lap' => 1,
                            'is_manual' => false,
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson(
            "/arrivals/{$arrival->id}/final-results",
            $payload,
            ['X-Api-Secret' => 'test-secret'],
        )->assertOk();

        $response = $this->getJson(
            "/arrivals/{$arrival->id}/final-results",
            ['X-Api-Secret' => 'test-secret'],
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.server_arrival_id', $arrival->id)
            ->assertJsonPath('data.server_race_id', 156)
            ->assertJsonPath('data.local_arrival_id', 101)
            ->assertJsonPath('data.finished_at_ms', 1719158400000)
            ->assertJsonPath('data.results.0.place', 1)
            ->assertJsonPath('data.results.0.user.name', 'Иван')
            ->assertJsonPath('data.results.0.laps.0.lap_number', 1)
            ->assertJsonPath('data.results.0.laps.1.lap_number', 2);
    }

    public function test_it_returns_404_when_arrival_not_found(): void
    {
        $this->getJson(
            '/arrivals/99999/final-results',
            ['X-Api-Secret' => 'test-secret'],
        )->assertNotFound();
    }

    public function test_it_returns_404_when_final_results_not_saved(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
            'finished_at' => null,
        ]);

        $this->getJson(
            "/arrivals/{$arrival->id}/final-results",
            ['X-Api-Secret' => 'test-secret'],
        )->assertNotFound();
    }
}
