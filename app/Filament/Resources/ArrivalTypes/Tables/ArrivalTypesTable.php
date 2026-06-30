<?php

namespace App\Filament\Resources\ArrivalTypes\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArrivalTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Код')
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('arrivals_count')
                    ->label('Заездов')
                    ->counts('arrivals'),
            ]);
    }
}
