<?php

namespace App\Filament\Resources\ArrivalResults\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LapsRelationManager extends RelationManager
{
    protected static string $relationship = 'laps';

    protected static ?string $title = 'Круги';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('lap_number')
                    ->label('№ круга')
                    ->required()
                    ->numeric(),
                TextInput::make('lap_time_ms')
                    ->label('Время круга (мс)')
                    ->required()
                    ->numeric(),
                TextInput::make('timestamp_ms')
                    ->label('Метка (мс)')
                    ->required()
                    ->numeric(),
                TextInput::make('position_on_lap')
                    ->label('Позиция на круге')
                    ->numeric(),
                Toggle::make('is_manual')
                    ->label('Ручной'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('lap_number')
            ->columns([
                TextColumn::make('lap_number')
                    ->label('Круг')
                    ->sortable(),
                TextColumn::make('lap_time_ms')
                    ->label('Время (мс)'),
                TextColumn::make('timestamp_ms')
                    ->label('Метка (мс)'),
                TextColumn::make('position_on_lap')
                    ->label('Позиция'),
                IconColumn::make('is_manual')
                    ->label('Ручной')
                    ->boolean(),
            ])
            ->headerActions([
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
}
