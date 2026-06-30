<?php

namespace App\Filament\Resources\ArrivalTypes\Pages;

use App\Filament\Resources\ArrivalTypes\ArrivalTypeResource;
use Filament\Resources\Pages\ListRecords;

class ListArrivalTypes extends ListRecords
{
    protected static string $resource = ArrivalTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
