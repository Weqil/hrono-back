<?php

namespace Tests\Feature;

use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesAction;
use App\Models\Arrival;
use App\Models\ArrivalResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateArrivalResultPlacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reassigns_places_by_total_time(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Test arrival',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '10:00',
            'arrival_grades' => [],
            'moto_race_id' => 1,
        ]);

        $slow = ArrivalResult::query()->create([
            'arrival_id' => $arrival->id,
            'server_race_id' => 1,
            'place' => 1,
            'total_laps' => 3,
            'total_time_ms' => 9000000,
            'best_lap_time_ms' => 0,
            'user_id' => 1,
            'name' => 'Slow',
            'surname' => 'Rider',
            'patronymic' => '',
            'start_number' => 1,
            'tag_id' => '',
            'grade' => '',
            'command' => '',
        ]);

        $fast = ArrivalResult::query()->create([
            'arrival_id' => $arrival->id,
            'server_race_id' => 1,
            'place' => 2,
            'total_laps' => 3,
            'total_time_ms' => 8133000,
            'best_lap_time_ms' => 0,
            'user_id' => 2,
            'name' => 'Fast',
            'surname' => 'Rider',
            'patronymic' => '',
            'start_number' => 2,
            'tag_id' => '',
            'grade' => '',
            'command' => '',
        ]);

        app(RecalculateArrivalResultPlacesAction::class)($arrival->id);

        $this->assertSame(1, $fast->fresh()->place);
        $this->assertSame(2, $slow->fresh()->place);
    }
}
