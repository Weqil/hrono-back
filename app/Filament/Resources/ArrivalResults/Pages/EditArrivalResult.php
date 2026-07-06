<?php

namespace App\Filament\Resources\ArrivalResults\Pages;

use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesAction;
use App\Filament\Resources\ArrivalResults\ArrivalResultResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditArrivalResult extends EditRecord
{
    protected static string $resource = ArrivalResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculatePlaces')
                ->label('Пересчитать места')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => $this->record->arrival?->isRegular() ?? false)
                ->requiresConfirmation()
                ->modalHeading('Пересчитать места?')
                ->modalDescription('Места будут назначены по общему времени: меньше время — выше место.')
                ->action(function (): void {
                    app(RecalculateArrivalResultPlacesAction::class)((int) $this->record->arrival_id);

                    Notification::make()
                        ->title('Места пересчитаны')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
