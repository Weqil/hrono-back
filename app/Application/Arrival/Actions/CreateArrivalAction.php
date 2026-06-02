<?php

namespace App\Application\Arrival\Actions;

use Illuminate\Http\Request;
use App\Models\Arrival;

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
        ]);

        return Arrival::query()->create($data);
    }
}
