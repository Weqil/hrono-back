<?php

namespace App\Application\Arrival\Support;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\Arrival;
use App\Models\ArrivalType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class ArrivalTypeResolver
{
    public static function resolveId(?string $slug = null, ?int $typeId = null): ?int
    {
        if (filled($typeId)) {
            return (int) $typeId;
        }

        $kind = ArrivalKind::tryFromSlug($slug);

        if ($kind === null) {
            return null;
        }

        return self::idFor($kind);
    }

    public static function resolvePathFilter(string $filter): int
    {
        if (ctype_digit($filter)) {
            $typeId = (int) $filter;

            $exists = ArrivalType::query()->whereKey($typeId)->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'arrival_type_id' => __('arrivals.arrival_type_not_found'),
                ]);
            }

            return $typeId;
        }

        $resolvedId = self::resolveId($filter);

        if ($resolvedId === null) {
            throw ValidationException::withMessages([
                'arrival_type' => __('arrivals.arrival_type_not_found'),
            ]);
        }

        return $resolvedId;
    }

    public static function idFor(ArrivalKind $kind): int
    {
        $resolvedId = ArrivalType::query()
            ->where('slug', $kind->value)
            ->value('id');

        if ($resolvedId === null) {
            throw ValidationException::withMessages([
                'arrival_type' => __('arrivals.arrival_type_not_found'),
            ]);
        }

        return (int) $resolvedId;
    }

    public static function defaultTypeId(): int
    {
        return self::idFor(ArrivalKind::Regular);
    }

    /**
     * @param  Builder<Arrival>  $query
     */
    public static function applyArrivalTypeFilter(Builder $query, int $arrivalTypeId): void
    {
        $regularTypeId = ArrivalType::query()
            ->where('slug', ArrivalKind::Regular->value)
            ->value('id');

        if ($regularTypeId !== null && $arrivalTypeId === (int) $regularTypeId) {
            $query->where(function (Builder $q) use ($arrivalTypeId): void {
                $q->where('arrival_type_id', $arrivalTypeId)
                    ->orWhereNull('arrival_type_id');
            });

            return;
        }

        $query->where('arrival_type_id', $arrivalTypeId);
    }

    public static function untypedFallbackForFilter(?int $arrivalTypeId): ?ArrivalType
    {
        if ($arrivalTypeId === null) {
            return null;
        }

        $regularType = ArrivalType::query()
            ->where('slug', ArrivalKind::Regular->value)
            ->first();

        if ($regularType === null || $arrivalTypeId !== $regularType->id) {
            return null;
        }

        return $regularType;
    }
}
