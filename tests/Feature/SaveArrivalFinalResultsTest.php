<?php

namespace Tests\Feature;

use App\Models\Arrival;
use App\Models\ArrivalResult;
use App\Models\ArrivalResultLap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SaveArrivalFinalResultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hrono.api_secret' => 'test-secret']);
    }

    public function test_it_saves_final_results_with_race_id_and_laps(): void
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
                    'total_laps' => 3,
                    'total_time_ms' => 754321,
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
                            'lap_time_ms' => 258021,
                            'timestamp_ms' => 509221,
                            'position_on_lap' => 1,
                            'is_manual' => false,
                        ],
                        [
                            'lap_number' => 3,
                            'lap_time_ms' => 245100,
                            'timestamp_ms' => 754321,
                            'position_on_lap' => 1,
                            'is_manual' => false,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson(
            "/arrivals/{$arrival->id}/final-results",
            $payload,
            ['X-Api-Secret' => 'test-secret'],
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', (string) $arrival->id);

        $arrival->refresh();

        $this->assertTrue($arrival->finished);
        $this->assertSame(101, $arrival->local_arrival_id);
        $this->assertNotNull($arrival->finished_at);

        $this->assertSame(1, ArrivalResult::query()->count());
        $this->assertSame(3, ArrivalResultLap::query()->count());

        $result = ArrivalResult::query()->first();

        $this->assertNotNull($result);
        $this->assertSame($arrival->id, $result->arrival_id);
        $this->assertSame(156, $result->server_race_id);
        $this->assertSame(1, $result->place);
        $this->assertSame(12, $result->user_id);
        $this->assertSame('Иван', $result->name);
    }

    public function test_it_rejects_race_id_mismatch(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);

        $response = $this->postJson(
            "/arrivals/{$arrival->id}/final-results",
            [
                'server_arrival_id' => $arrival->id,
                'server_race_id' => 999,
                'local_arrival_id' => 101,
                'finished_at_ms' => 1719158400000,
                'results' => [
                    [
                        'place' => 1,
                        'total_laps' => 1,
                        'total_time_ms' => 1000,
                        'best_lap_time_ms' => 1000,
                        'user' => [
                            'id' => 1,
                            'name' => 'Test',
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
            ],
            ['X-Api-Secret' => 'test-secret'],
        );

        $response->assertStatus(422);
        $this->assertSame(0, ArrivalResult::query()->count());
    }

    public function test_it_replaces_existing_final_results_on_resubmit(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);

        $basePayload = [
            'server_arrival_id' => $arrival->id,
            'server_race_id' => 156,
            'local_arrival_id' => 101,
            'finished_at_ms' => 1719158400000,
            'results' => [
                [
                    'place' => 1,
                    'total_laps' => 1,
                    'total_time_ms' => 1000,
                    'best_lap_time_ms' => 1000,
                    'user' => [
                        'id' => 1,
                        'name' => 'First',
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

        $headers = ['X-Api-Secret' => 'test-secret'];

        $this->postJson("/arrivals/{$arrival->id}/final-results", $basePayload, $headers)
            ->assertOk();

        $basePayload['results'][0]['user']['name'] = 'Second';

        $this->postJson("/arrivals/{$arrival->id}/final-results", $basePayload, $headers)
            ->assertOk();

        $this->assertSame(1, ArrivalResult::query()->count());
        $this->assertSame('Second', ArrivalResult::query()->value('name'));
    }

    public function test_it_accepts_client_like_payload_with_nulls_empty_laps_and_floats(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);

        $payload = [
            'server_arrival_id' => $arrival->id,
            'server_race_id' => 156,
            'local_arrival_id' => 101,
            'finished_at_ms' => 1719158400000.0,
            'results' => [
                [
                    'place' => 1.0,
                    'total_laps' => 0,
                    'total_time_ms' => 0.0,
                    'best_lap_time_ms' => null,
                    'user' => [
                        'id' => 1,
                        'name' => 'Без кругов',
                        'surname' => '',
                        'patronymic' => null,
                        'start_number' => 7.0,
                        'tag_id' => '',
                        'grade' => '',
                        'command' => '',
                    ],
                    'laps' => [],
                ],
                [
                    'place' => 2,
                    'total_laps' => 1.0,
                    'total_time_ms' => 1000.7,
                    'best_lap_time_ms' => 1000.2,
                    'user' => [
                        'id' => 2,
                        'name' => 'С кругом',
                        'surname' => '',
                        'patronymic' => '',
                        'start_number' => 8,
                        'tag_id' => '',
                        'grade' => '',
                        'command' => '',
                    ],
                    'laps' => [
                        [
                            'lap_number' => 1,
                            'lap_time_ms' => 1000.2,
                            'timestamp_ms' => 1000.9,
                            'position_on_lap' => null,
                            'is_manual' => 0,
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

        $this->assertSame(2, ArrivalResult::query()->count());
        $this->assertSame(1, ArrivalResultLap::query()->count());
        $this->assertSame(0, ArrivalResult::query()->where('user_id', 1)->value('best_lap_time_ms'));
        $this->assertSame(0, ArrivalResultLap::query()->value('position_on_lap'));
    }
}
