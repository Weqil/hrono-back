<?php

namespace Tests\Feature;

use App\Application\Arrival\Enums\ArrivalKind;
use Database\Seeders\ArrivalTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetArrivalTypesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hrono.api_secret' => 'test-secret']);
        $this->seed(ArrivalTypeSeeder::class);
    }

    public function test_it_returns_all_arrival_types_for_frontend(): void
    {
        $response = $this->getJson('/arrival-types', [
            'X-Api-Secret' => 'test-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data.types')
            ->assertJsonPath('data.types.0.slug', ArrivalKind::Qualification->value)
            ->assertJsonPath('data.types.0.name', 'Квалификационный')
            ->assertJsonPath('data.types.1.slug', ArrivalKind::Regular->value)
            ->assertJsonPath('data.types.1.name', 'Обычный');

        $this->assertNotNull($response->json('data.types.0.id'));
        $this->assertNotNull($response->json('data.types.1.id'));
    }
}
