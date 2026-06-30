<?php

namespace App\Application\Arrival\Actions;

use App\Application\Arrival\Enums\ArrivalKind;
use App\Models\Arrival;
use App\Models\ArrivalType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CreateArrivalAction
{
    public function execute(Request $request): Arrival
    {
        $data = $request->validate([
            'moto_race_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'finished' => ['sometimes', 'boolean'],
            'round_min_time' => ['sometimes', 'integer', 'min:0'],
            'time' => ['required', 'string', 'max:255'],
            'arrival_grades' => ['sometimes', 'array'],
            'arrival_grades.*' => ['string'],
            'arrival_type_id' => ['sometimes', 'nullable', 'integer', 'exists:arrival_types,id'],
            'arrival_type' => ['sometimes', 'nullable', 'string', Rule::enum(ArrivalKind::class)],
        ]);

        $data['arrival_type_id'] = $this->resolveArrivalTypeId($data);

        unset($data['arrival_type']);

        return Arrival::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveArrivalTypeId(array $data): ?int
    {
        if (isset($data['arrival_type_id'])) {
            return (int) $data['arrival_type_id'];
        }

        $kind = ArrivalKind::tryFromSlug($data['arrival_type'] ?? null);

        if ($kind === null) {
            return null;
        }

        $typeId = ArrivalType::query()
            ->where('slug', $kind->value)
            ->value('id');

        if ($typeId === null) {
            throw ValidationException::withMessages([
                'arrival_type' => __('arrivals.arrival_type_not_found'),
            ]);
        }

        return (int) $typeId;
    }
}
