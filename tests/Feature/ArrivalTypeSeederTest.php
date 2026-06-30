<?php

namespace Tests\Feature;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\ArrivalType;
use Database\Seeders\ArrivalTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArrivalTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_only_enum_types(): void
    {
        $this->seed(ArrivalTypeSeeder::class);

        $this->assertDatabaseCount('arrival_types', 2);
        $this->assertDatabaseHas('arrival_types', [
            'slug' => ArrivalKind::Qualification->value,
            'name' => 'Квалификационный',
        ]);
        $this->assertDatabaseHas('arrival_types', [
            'slug' => ArrivalKind::Regular->value,
            'name' => 'Обычный',
        ]);

        ArrivalType::query()->each(function (ArrivalType $type): void {
            $this->assertInstanceOf(ArrivalKind::class, $type->slug);
        });
    }
}
