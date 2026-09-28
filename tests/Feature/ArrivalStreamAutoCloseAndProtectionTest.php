<?php

namespace Tests\Feature;

use App\Jobs\AutoCloseArrivalStreamJob;
use App\Models\Arrival;
use Database\Seeders\ArrivalTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ArrivalStreamAutoCloseAndProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'hrono.api_secret' => 'test-secret',
            'hrono.moto_api_url' => 'https://moto.test/api/',
            'hrono.stream_auto_close_grace_minutes' => 10,
        ]);

        $this->seed(ArrivalTypeSeeder::class);
    }

    public function test_open_stream_schedules_auto_close_after_duration_plus_grace(): void
    {
        Queue::fake();

        Http::fake([
            'https://moto.test/api/races/156/stream/open' => Http::response([
                'ok' => true,
                'stream_id' => 'moto-stream-abc',
                'stream_opened_at' => 1_700_000_000_000,
            ], 200),
        ]);

        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);

        $this->postJson(
            "/arrivals/{$arrival->id}/stream/open",
            [],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();

        $arrival->refresh();

        $this->assertNotNull($arrival->moto_stream_opened_at);
        $this->assertSame('moto-stream-abc', $arrival->moto_stream_id);
        $this->assertSame(1_700_000_000_000, $arrival->moto_stream_opened_at->getTimestampMs());
        $this->assertNotNull($arrival->stream_auto_close_at);
        $this->assertSame(
            $arrival->moto_stream_opened_at->copy()->addMinutes(20)->timestamp,
            $arrival->stream_auto_close_at->timestamp,
        );
        $this->assertSame('moto-token', $arrival->moto_stream_bearer);

        Queue::assertPushed(AutoCloseArrivalStreamJob::class, function (AutoCloseArrivalStreamJob $job) use ($arrival): bool {
            return $job->arrivalId === (int) $arrival->id;
        });
    }

    public function test_live_results_include_stream_id_and_treat_409_as_ignored(): void
    {
        Http::fake([
            'https://moto.test/api/hrono/races/156/results' => Http::response([
                'status' => 'ignored',
            ], 409),
        ]);

        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $arrival->forceFill([
            'moto_stream_opened_at' => now(),
            'moto_stream_id' => 'stream-xyz',
            'stream_auto_close_at' => now()->addMinutes(20),
        ])->save();

        $this->postJson(
            "/arrivals/{$arrival->id}/results",
            [
                [
                    'id' => 1,
                    'lapCount' => 1,
                    'totalRaceTimeMs' => 50000,
                    'lastLapTimestampMs' => 50000,
                    'participantData' => [
                        'id' => 1,
                        'name' => 'Иван',
                        'surname' => 'Иванов',
                        'start_number' => 1,
                    ],
                    'laps' => [
                        ['lapTimeMs' => 50000],
                    ],
                ],
            ],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();

        Http::assertSent(function ($request) use ($arrival): bool {
            if ($request->url() !== 'https://moto.test/api/hrono/races/156/results') {
                return false;
            }

            $body = $request->data();

            return ($body['arrival_meta']['stream_id'] ?? null) === 'stream-xyz'
                && ($body['arrival_meta']['arrival_name'] ?? null) === $arrival->name
                && array_key_exists('stream_opened_at', $body['arrival_meta'] ?? [])
                && is_array($body['participants'] ?? null);
        });
    }

    public function test_auto_close_command_closes_due_stream_in_moto(): void
    {
        Http::fake([
            'https://moto.test/api/races/156/stream/close' => Http::response(['ok' => true], 200),
        ]);

        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $arrival->forceFill([
            'moto_stream_opened_at' => now()->subMinutes(25),
            'stream_auto_close_at' => now()->subMinute(),
            'moto_stream_bearer' => 'stored-token',
        ])->save();

        $this->artisan('arrivals:auto-close-expired')->assertSuccessful();

        $arrival->refresh();
        $this->assertNotNull($arrival->moto_stream_closed_at);
        $this->assertNull($arrival->stream_auto_close_at);
        $this->assertNull($arrival->moto_stream_bearer);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://moto.test/api/races/156/stream/close'
                && $request->hasHeader('Authorization', 'Bearer stored-token');
        });
    }

    public function test_delayed_results_from_inactive_arrival_are_not_forwarded_to_moto(): void
    {
        Http::fake([
            'https://moto.test/api/hrono/races/156/results' => Http::response(['ok' => true], 200),
        ]);

        $stale = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $stale->forceFill([
            'moto_stream_opened_at' => now()->subMinutes(30),
            'moto_stream_closed_at' => now()->subMinutes(5),
        ])->save();

        $active = Arrival::query()->create([
            'name' => 'Заезд 2',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $active->forceFill([
            'moto_stream_opened_at' => now()->subMinute(),
            'stream_auto_close_at' => now()->addMinutes(20),
        ])->save();

        $this->postJson(
            "/arrivals/{$stale->id}/results",
            [
                [
                    'id' => 99,
                    'lapCount' => 5,
                    'totalRaceTimeMs' => 100000,
                    'lastLapTimestampMs' => 100000,
                    'participantData' => [
                        'id' => 99,
                        'name' => 'Чужой',
                        'surname' => 'Гонщик',
                        'start_number' => 99,
                    ],
                    'laps' => [
                        ['lapTimeMs' => 50000],
                    ],
                ],
            ],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer moto-token',
            ],
        )->assertOk();

        Http::assertNothingSent();
    }

    public function test_due_auto_close_blocks_and_closes_on_late_results(): void
    {
        Http::fake([
            'https://moto.test/api/races/156/stream/close' => Http::response(['ok' => true], 200),
            'https://moto.test/api/hrono/races/156/results' => Http::response(['ok' => true], 200),
        ]);

        $arrival = Arrival::query()->create([
            'name' => 'Заезд 1',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 156,
        ]);
        $arrival->forceFill([
            'moto_stream_opened_at' => now()->subMinutes(25),
            'stream_auto_close_at' => now()->subMinute(),
            'moto_stream_bearer' => 'stored-token',
        ])->save();

        $this->postJson(
            "/arrivals/{$arrival->id}/results",
            [
                [
                    'id' => 1,
                    'lapCount' => 1,
                    'totalRaceTimeMs' => 50000,
                    'lastLapTimestampMs' => 50000,
                    'participantData' => [
                        'id' => 1,
                        'name' => 'Иван',
                        'surname' => 'Иванов',
                        'start_number' => 1,
                    ],
                    'laps' => [
                        ['lapTimeMs' => 50000],
                    ],
                ],
            ],
            [
                'X-Api-Secret' => 'test-secret',
                'Authorization' => 'Bearer late-token',
            ],
        )->assertOk();

        $arrival->refresh();
        $this->assertNotNull($arrival->moto_stream_closed_at);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://moto.test/api/races/156/stream/close');
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), '/results'));
    }
}
