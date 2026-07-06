<?php

namespace Tests\Feature;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\Arrival;
use App\Models\ArrivalType;
use Database\Seeders\ArrivalTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateArrivalWithTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hrono.api_secret' => 'test-secret']);

        $this->seed(ArrivalTypeSeeder::class);
    }

    public function test_it_creates_arrival_with_type_slug(): void
    {
        $response = $this->postJson('/arrivals', [
            'moto_race_id' => 15,
            'name' => 'Квалификация',
            'time' => '10:00',
            'arrival_type' => ArrivalKind::Qualification->value,
        ], [
            'X-Api-Secret' => 'test-secret',
        ]);

        $response->assertCreated();

        $arrival = Arrival::query()->first();
        $type = ArrivalType::query()->where('slug', ArrivalKind::Qualification)->first();

        $this->assertNotNull($arrival);
        $this->assertNotNull($type);
        $this->assertSame($type->id, $arrival->arrival_type_id);
    }

    public function test_it_defaults_arrival_type_to_regular_when_not_specified(): void
    {
        $response = $this->postJson('/arrivals', [
            'moto_race_id' => 15,
            'name' => 'Заезд',
            'time' => '10:00',
        ], [
            'X-Api-Secret' => 'test-secret',
        ]);

        $response->assertCreated();

        $arrival = Arrival::query()->first();
        $regularTypeId = ArrivalType::query()
            ->where('slug', ArrivalKind::Regular->value)
            ->value('id');

        $this->assertNotNull($arrival);
        $this->assertSame($regularTypeId, $arrival->arrival_type_id);
    }
}
