<?php

namespace Tests\Unit;

use App\Models\Arrival;
use App\Support\ArrivalStreamAutoClose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArrivalStreamAutoCloseHardCapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'hrono.stream_auto_close_grace_minutes' => 10,
            'hrono.stream_auto_close_max_hours' => 1,
        ]);
    }

    public function test_due_at_is_clamped_to_hard_cap(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'Long',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '02:00:00',
            'arrival_grades' => [],
            'moto_race_id' => 1,
        ]);

        $opened = now();
        $due = ArrivalStreamAutoClose::dueAt($arrival, $opened);
        $hardCap = ArrivalStreamAutoClose::hardCapAt($opened);

        $this->assertTrue($due->equalTo($hardCap));
    }

    public function test_extend_from_activity_does_not_pass_hard_cap(): void
    {
        $arrival = Arrival::query()->create([
            'name' => 'A',
            'finished' => false,
            'round_min_time' => 60,
            'time' => '00:10:00',
            'arrival_grades' => [],
            'moto_race_id' => 1,
        ]);

        $opened = now()->subMinutes(55);
        $arrival->forceFill([
            'moto_stream_opened_at' => $opened,
            'stream_auto_close_at' => $opened->copy()->addMinutes(20),
        ])->save();

        $due = ArrivalStreamAutoClose::extendFromActivity($arrival, now());
        $hardCap = ArrivalStreamAutoClose::hardCapAt($opened);

        $this->assertNotNull($due);
        $this->assertTrue($due->lessThanOrEqualTo($hardCap));
    }
}
