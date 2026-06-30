<?php

namespace App\Filament\Resources\Arrivals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArrivalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('moto_race_id')
                    ->label('ID гонки')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): void {
                        if (! is_numeric($search)) {
                            return;
                        }

                        $query->where('moto_race_id', (int) $search);
                    }),
                TextColumn::make('arrivalType.name')
                    ->label('Тип')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('time')
                    ->label('Время'),
                IconColumn::make('finished')
                    ->label('Завершён')
                    ->boolean(),
                TextColumn::make('results_count')
                    ->label('Участников')
                    ->counts('results'),
                TextColumn::make('finished_at')
                    ->label('Завершён в')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('moto_stream_opened_at')
                    ->label('Эфир открыт')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('moto_stream_closed_at')
                    ->label('Эфир закрыт')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('arrival_type_id')
                    ->label('Тип заезда')
                    ->relationship('arrivalType', 'name')
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
