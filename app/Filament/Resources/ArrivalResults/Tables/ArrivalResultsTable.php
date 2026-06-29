<?php

namespace App\Filament\Resources\ArrivalResults\Tables;

use App\Filament\Support\RaceTimeForm;
use App\Models\Arrival;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ArrivalResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('place')
            ->columns([
                TextColumn::make('arrival.name')
                    ->label('Заезд')
                    ->searchable(),
                TextColumn::make('arrival.moto_race_id')
                    ->label('ID гонки')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('place')
                    ->label('Место')
                    ->sortable(),
                TextColumn::make('start_number')
                    ->label('№')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                TextColumn::make('surname')
                    ->label('Фамилия')
                    ->searchable(),
                TextColumn::make('total_laps')
                    ->label('Кругов')
                    ->sortable(),
                RaceTimeForm::totalTimeMsColumn(),
                TextColumn::make('grade')
                    ->label('Класс'),
            ])
            ->filters([
                SelectFilter::make('arrival_id')
                    ->label('Заезд')
                    ->relationship(
                        'arrival',
                        'name',
                        fn ($query) => $query->orderByDesc('id'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Arrival $record): string => sprintf('%s (#%d)', $record->name, $record->id),
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
