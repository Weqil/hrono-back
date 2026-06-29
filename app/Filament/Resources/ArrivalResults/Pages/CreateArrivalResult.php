<?php

namespace App\Filament\Resources\ArrivalResults\Pages;

use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesAction;
use App\Filament\Resources\ArrivalResults\ArrivalResultResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArrivalResult extends CreateRecord
{
    protected static string $resource = ArrivalResultResource::class;

    protected function afterCreate(): void
    {
        app(RecalculateArrivalResultPlacesAction::class)((int) $this->record->arrival_id);
    }
}
