<?php

namespace Database\Seeders;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\ArrivalType;
use Illuminate\Database\Seeder;

final class ArrivalTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ArrivalKind::cases() as $kind) {
            ArrivalType::query()->updateOrCreate(
                ['slug' => $kind->value],
                ['name' => $kind->label()],
            );
        }

        ArrivalType::query()
            ->whereNotIn('slug', array_column(ArrivalKind::cases(), 'value'))
            ->delete();
    }
}
