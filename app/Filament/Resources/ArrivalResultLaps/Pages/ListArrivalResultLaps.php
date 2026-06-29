<?php

namespace App\Filament\Resources\ArrivalResultLaps\Pages;

use App\Filament\Resources\ArrivalResultLaps\ArrivalResultLapResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArrivalResultLaps extends ListRecords
{
    protected static string $resource = ArrivalResultLapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
