<?php

namespace App\Filament\Resources\ArrivalResults\Pages;

use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesAction;
use App\Filament\Resources\ArrivalResults\ArrivalResultResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArrivalResult extends EditRecord
{
    protected static string $resource = ArrivalResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(function (): void {
                    $arrivalId = (int) $this->record->arrival_id;
                    app(RecalculateArrivalResultPlacesAction::class)($arrivalId);
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->recalculatePlaces();
    }

    private function recalculatePlaces(): void
    {
        app(RecalculateArrivalResultPlacesAction::class)((int) $this->record->arrival_id);
    }
}
