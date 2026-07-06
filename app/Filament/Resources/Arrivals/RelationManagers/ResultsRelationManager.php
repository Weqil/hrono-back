<?php

namespace App\Filament\Resources\Arrivals\RelationManagers;

use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesAction;
use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesByBestLapAction;
use App\Filament\Support\RaceTimeForm;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';

    protected static ?string $title = 'Результаты участников';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Имя')
                    ->required(),
                TextInput::make('surname')
                    ->label('Фамилия'),
                TextInput::make('place')
                    ->label('Место')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('start_number')
                    ->label('Стартовый номер')
                    ->required()
                    ->numeric(),
                TextInput::make('total_laps')
                    ->label('Кругов')
                    ->required()
                    ->numeric(),
                RaceTimeForm::totalTimeMsInput(),
                RaceTimeForm::bestLapTimeMsInput(),
                TextInput::make('grade')
                    ->label('Класс'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('place')
            ->columns([
                TextColumn::make('place')
                    ->label('Место')
                    ->sortable(),
                TextColumn::make('start_number')
                    ->label('№'),
                TextColumn::make('name')
                    ->label('Имя'),
                TextColumn::make('surname')
                    ->label('Фамилия'),
                TextColumn::make('total_laps')
                    ->label('Кругов'),
                RaceTimeForm::totalTimeMsColumn(),
                RaceTimeForm::totalTimeMsColumn('best_lap_time_ms', 'Лучший круг'),
                TextColumn::make('laps_count')
                    ->label('Кругов в БД')
                    ->counts('laps'),
            ])
            ->headerActions([
                Action::make('recalculatePlaces')
                    ->label('Пересчитать места')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => $this->getOwnerRecord()->isRegular())
                    ->requiresConfirmation()
                    ->modalHeading('Пересчитать места?')
                    ->modalDescription('Места будут назначены по общему времени: меньше время — выше место.')
                    ->action(function (): void {
                        $this->recalculatePlaces();

                        Notification::make()
                            ->title('Места пересчитаны')
                            ->success()
                            ->send();
                    }),
                Action::make('recalculatePlacesByBestLap')
                    ->label('Пересчитать по лучшему кругу')
                    ->icon('heroicon-o-bolt')
                    ->requiresConfirmation()
                    ->modalHeading('Пересчитать по лучшему кругу?')
                    ->modalDescription('Лучший круг пересчитается без учёта первого круга, места назначатся по лучшему кругу: быстрее круг — выше место.')
                    ->action(function (): void {
                        $this->recalculatePlacesByBestLap();

                        Notification::make()
                            ->title('Места пересчитаны по лучшему кругу')
                            ->success()
                            ->send();
                    }),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function recalculatePlaces(): void
    {
        app(RecalculateArrivalResultPlacesAction::class)($this->getOwnerRecord()->getKey());
    }

    private function recalculatePlacesByBestLap(): void
    {
        app(RecalculateArrivalResultPlacesByBestLapAction::class)($this->getOwnerRecord()->getKey());
    }
}
