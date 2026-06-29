<?php

namespace App\Filament\Resources\ArrivalResultLaps\Pages;

use App\Filament\Resources\ArrivalResultLaps\ArrivalResultLapResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArrivalResultLap extends EditRecord
{
    protected static string $resource = ArrivalResultLapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
