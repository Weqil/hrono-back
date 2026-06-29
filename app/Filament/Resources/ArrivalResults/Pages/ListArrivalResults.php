<?php

namespace App\Filament\Resources\ArrivalResults\Pages;

use App\Filament\Resources\ArrivalResults\ArrivalResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArrivalResults extends ListRecords
{
    protected static string $resource = ArrivalResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
