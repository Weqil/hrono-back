<?php

namespace Tests\Feature;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\Arrival;
use App\Models\ArrivalType;
use Database\Seeders\ArrivalTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ArrivalStreamCloseWithFinalResultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'hrono.api_secret' => 'test-secret',
            'hrono.moto_api_url' => 'https://moto.test/api/',
        ]);

        $this->seed(ArrivalTypeSeeder::class);
    }

    public function test_it_closes_stream_without_saving_final_results(): void
    {
        Http::fake([
            'https://moto.test/api/races/156/stream/close' => Http::response(['ok' => true], 200),
        ]);

        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $arrival->forceFill(['moto_stream_opened_at' => now()])->save();

        $this->postJson(
            "/arrivals/{$arrival->id}/stream/close",
            [],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();

        $arrival->refresh();
        $this->assertNotNull($arrival->moto_stream_closed_at);
        $this->assertFalse($arrival->hasFinalResults());
        $this->assertFalse($arrival->finished);
        $this->assertDatabaseCount('arrival_results', 0);
    }

    public function test_it_returns_success_when_stream_is_already_closed(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $arrival->forceFill([
            'moto_stream_opened_at' => now()->subMinute(),
            'moto_stream_closed_at' => now(),
        ])->save();

        $this->postJson(
            "/arrivals/{$arrival->id}/stream/close",
            [],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();
    }

    public function test_it_does_not_close_moto_stream_when_another_arrival_is_active(): void
    {
        Http::fake([
            'https://moto.test/api/races/156/stream/close' => Http::response(['ok' => true], 200),
        ]);

        $staleArrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $staleArrival->forceFill(['moto_stream_opened_at' => now()->subMinute()])->save();

        $activeArrival = Arrival::query()->create([
            'name' => 'Заезд 2',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '11:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $activeArrival->forceFill(['moto_stream_opened_at' => now()])->save();

        $this->postJson(
            "/arrivals/{$staleArrival->id}/stream/close",
            [],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();

        $staleArrival->refresh();
        $activeArrival->refresh();

        $this->assertNotNull($staleArrival->moto_stream_closed_at);
        $this->assertNull($activeArrival->moto_stream_closed_at);
        Http::assertNothingSent();
    }

    public function test_it_closes_stream_after_saving_final_results(): void
    {
        Http::fake([
            'https://moto.test/api/races/156/stream/close' => Http::response(['ok' => true], 200),
        ]);

        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $arrival->forceFill(['moto_stream_opened_at' => now()])->save();

        $this->postJson(
            "/arrivals/{$arrival->id}/final-results",
            $this->finalResultsPayload($arrival->id),
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();

        $arrival->refresh();

        $this->assertTrue($arrival->hasFinalResults());
        $this->assertNotNull($arrival->moto_stream_closed_at);

        Http::assertSent(static fn ($request) => $request->url() === 'https://moto.test/api/races/156/stream/close');
    }

    public function test_it_does_not_close_moto_when_saving_final_results_for_non_active_arrival(): void
    {
        Http::fake([
            'https://moto.test/api/races/156/stream/close' => Http::response(['ok' => true], 200),
        ]);

        $staleArrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $staleArrival->forceFill(['moto_stream_opened_at' => now()->subMinute()])->save();

        $activeArrival = Arrival::query()->create([
            'name' => 'Заезд 2',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '11:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $activeArrival->forceFill(['moto_stream_opened_at' => now()])->save();

        $this->postJson(
            "/arrivals/{$staleArrival->id}/final-results",
            $this->finalResultsPayload($staleArrival->id),
            ['X-Api-Secret' => 'test-secret'],
        )->assertOk();

        $staleArrival->refresh();
        $activeArrival->refresh();

        $this->assertTrue($staleArrival->hasFinalResults());
        $this->assertNotNull($staleArrival->moto_stream_closed_at);
        $this->assertNull($activeArrival->moto_stream_closed_at);
        Http::assertNothingSent();
    }

    public function test_it_closes_previous_open_stream_when_opening_new_arrival_on_same_race(): void
    {
        Http::fake([
            'https://moto.test/api/races/156/stream/close' => Http::response(['ok' => true], 200),
            'https://moto.test/api/races/156/stream/open' => Http::response(['ok' => true], 200),
        ]);

        $previousArrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $previousArrival->forceFill(['moto_stream_opened_at' => now()])->save();

        $regularTypeId = ArrivalType::query()
            ->where('slug', ArrivalKind::Regular->value)
            ->value('id');

        $newArrival = Arrival::query()->create([
            'name' => 'Заезд 2',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '11:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
            'arrival_type_id' => $regularTypeId,
        ]);

        $this->postJson(
            "/arrivals/{$newArrival->id}/stream/open",
            [],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();

        $previousArrival->refresh();
        $newArrival->refresh();

        $this->assertNotNull($previousArrival->moto_stream_closed_at);
        $this->assertNotNull($newArrival->moto_stream_opened_at);
        $this->assertNull($newArrival->moto_stream_closed_at);

        Http::assertSent(static fn ($request) => $request->url() === 'https://moto.test/api/races/156/stream/open'
            && $request['arrival_name'] === 'Заезд 2'
            && $request['metadata']['arrival_type_id'] === $regularTypeId);
    }

    public function test_it_requires_moto_bearer_to_close_stream_when_saving_final_results(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $arrival->forceFill(['moto_stream_opened_at' => now()])->save();

        $this->postJson(
            "/arrivals/{$arrival->id}/final-results",
            $this->finalResultsPayload($arrival->id),
            ['X-Api-Secret' => 'test-secret'],
        )->assertStatus(422);

        $arrival->refresh();

        $this->assertFalse($arrival->hasFinalResults());
        $this->assertNull($arrival->moto_stream_closed_at);
    }

    /**
     * @return array<string, mixed>
     */
    private function finalResultsPayload(int $arrivalId): array
    {
        return [
            'server_arrival_id' => $arrivalId,
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
        ];
    }
}
