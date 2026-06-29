<?php

namespace App\Filament\Resources\Arrivals\Pages;

use App\Filament\Resources\Arrivals\ArrivalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArrival extends EditRecord
{
    protected static string $resource = ArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
