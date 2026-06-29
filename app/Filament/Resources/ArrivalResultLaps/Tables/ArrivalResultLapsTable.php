<?php

namespace App\Filament\Resources\ArrivalResultLaps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArrivalResultLapsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('arrivalResult.name')
                    ->searchable(),
                TextColumn::make('lap_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lap_time_ms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('timestamp_ms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('position_on_lap')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_manual')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
