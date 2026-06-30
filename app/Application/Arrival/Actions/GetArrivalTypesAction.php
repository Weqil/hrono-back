<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\ArrivalType;

final class GetArrivalTypesAction
{
    /**
     * @return list<array{id: int|null, slug: string, name: string}>
     */
    public function execute(): array
    {
        $typesBySlug = ArrivalType::query()
            ->orderBy('id')
            ->get()
            ->keyBy(fn (ArrivalType $type): string => $type->slug->value);

        return array_map(
            static fn (ArrivalKind $kind): array => [
                'id' => $typesBySlug->get($kind->value)?->id,
                'slug' => $kind->value,
                'name' => $kind->label(),
            ],
            ArrivalKind::cases(),
        );
    }
}
