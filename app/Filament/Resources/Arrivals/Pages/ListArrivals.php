<?php

namespace App\Filament\Resources\Arrivals\Pages;

use App\Filament\Resources\Arrivals\ArrivalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArrivals extends ListRecords
{
    protected static string $resource = ArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
