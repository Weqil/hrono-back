<?php

namespace Tests\Feature;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\Arrival;
use App\Models\ArrivalType;
use Database\Seeders\ArrivalTypeSeeder;
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
            ->assertJsonPath('data.arrivals.1.results.0.user.name', 'Второй')
            ->assertJsonPath('data.arrivals.0.arrival_type', null)
            ->assertJsonPath('data.arrivals.1.arrival_type', null);
    }

    public function test_it_filters_arrivals_by_arrival_type_query_parameter(): void
    {
        $this->seed(ArrivalTypeSeeder::class);

        $raceId = 156;
        $qualificationTypeId = ArrivalType::query()
            ->where('slug', ArrivalKind::Qualification->value)
            ->value('id');
        $regularTypeId = ArrivalType::query()
            ->where('slug', ArrivalKind::Regular->value)
            ->value('id');

        $qualificationArrival = Arrival::query()->create([
            'name' => 'Квалификация',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => $raceId,
            'arrival_type_id' => $qualificationTypeId,
        ]);

        $regularArrival = Arrival::query()->create([
            'name' => 'Финал',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '11:00',
            'arrival_grades' => [],
            'moto_race_id' => $raceId,
            'arrival_type_id' => $regularTypeId,
        ]);

        $this->postJson(
            "/arrivals/{$qualificationArrival->id}/final-results",
            $this->payload($qualificationArrival->id, $raceId, 101, 1719158400000, 'Квалификационный'),
            ['X-Api-Secret' => 'test-secret'],
        )->assertOk();

        $this->postJson(
            "/arrivals/{$regularArrival->id}/final-results",
            $this->payload($regularArrival->id, $raceId, 102, 1719158500000, 'Обычный'),
            ['X-Api-Secret' => 'test-secret'],
        )->assertOk();

        $this->getJson(
            "/races/{$raceId}/final-results?arrival_type=qualification",
            ['X-Api-Secret' => 'test-secret'],
        )
            ->assertOk()
            ->assertJsonCount(1, 'data.arrivals')
            ->assertJsonPath('data.arrivals.0.server_arrival_id', $qualificationArrival->id)
            ->assertJsonPath('data.arrivals.0.arrival_type.slug', ArrivalKind::Qualification->value);
    }

    public function test_it_filters_arrivals_by_arrival_type_id_including_legacy_untyped_as_regular(): void
    {
        $this->seed(ArrivalTypeSeeder::class);

        $raceId = 156;
        $qualificationTypeId = ArrivalType::query()
            ->where('slug', ArrivalKind::Qualification->value)
            ->value('id');
        $regularTypeId = ArrivalType::query()
            ->where('slug', ArrivalKind::Regular->value)
            ->value('id');

        $legacyRegularArrival = Arrival::query()->create([
            'name' => 'Старый заезд',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '09:00',
            'arrival_grades' => [],
            'moto_race_id' => $raceId,
            'arrival_type_id' => null,
        ]);

        $qualificationArrival = Arrival::query()->create([
            'name' => 'Квалификация',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => $raceId,
            'arrival_type_id' => $qualificationTypeId,
        ]);

        $regularArrival = Arrival::query()->create([
            'name' => 'Финал',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '11:00',
            'arrival_grades' => [],
            'moto_race_id' => $raceId,
            'arrival_type_id' => $regularTypeId,
        ]);

        foreach ([$legacyRegularArrival, $qualificationArrival, $regularArrival] as $index => $arrival) {
            $this->postJson(
                "/arrivals/{$arrival->id}/final-results",
                $this->payload($arrival->id, $raceId, 101 + $index, 1719158400000 + ($index * 100000), $arrival->name),
                ['X-Api-Secret' => 'test-secret'],
            )->assertOk();
        }

        $this->getJson(
            "/races/{$raceId}/final-results?arrival_type_id={$regularTypeId}",
            ['X-Api-Secret' => 'test-secret'],
        )
            ->assertOk()
            ->assertJsonCount(2, 'data.arrivals')
            ->assertJsonPath('data.arrivals.0.server_arrival_id', $legacyRegularArrival->id)
            ->assertJsonPath('data.arrivals.0.arrival_type.id', $regularTypeId)
            ->assertJsonPath('data.arrivals.0.arrival_type.slug', ArrivalKind::Regular->value)
            ->assertJsonPath('data.arrivals.1.server_arrival_id', $regularArrival->id)
            ->assertJsonPath('data.arrivals.1.arrival_type.slug', ArrivalKind::Regular->value);

        $this->getJson(
            "/races/{$raceId}/final-results?arrival_type_id={$qualificationTypeId}",
            ['X-Api-Secret' => 'test-secret'],
        )
            ->assertOk()
            ->assertJsonCount(1, 'data.arrivals')
            ->assertJsonPath('data.arrivals.0.server_arrival_id', $qualificationArrival->id)
            ->assertJsonPath('data.arrivals.0.arrival_type.slug', ArrivalKind::Qualification->value);

        $this->getJson(
            "/races/{$raceId}/final-results/type/{$qualificationTypeId}",
            ['X-Api-Secret' => 'test-secret'],
        )
            ->assertOk()
            ->assertJsonCount(1, 'data.arrivals')
            ->assertJsonPath('data.arrivals.0.server_arrival_id', $qualificationArrival->id);

        $this->getJson(
            "/races/{$raceId}/final-results/type/qualification",
            ['X-Api-Secret' => 'test-secret'],
        )
            ->assertOk()
            ->assertJsonCount(1, 'data.arrivals')
            ->assertJsonPath('data.arrivals.0.server_arrival_id', $qualificationArrival->id);
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
