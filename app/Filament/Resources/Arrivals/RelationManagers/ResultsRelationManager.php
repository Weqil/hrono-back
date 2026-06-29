<?php

namespace App\Filament\Resources\Arrivals\RelationManagers;

use App\Application\Arrival\Actions\RecalculateArrivalResultPlacesAction;
use App\Filament\Support\RaceTimeForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('laps_count')
                    ->label('Кругов в БД')
                    ->counts('laps'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(fn () => $this->recalculatePlaces()),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn () => $this->recalculatePlaces()),
                DeleteAction::make()
                    ->after(fn () => $this->recalculatePlaces()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn () => $this->recalculatePlaces()),
                ]),
            ]);
    }

    private function recalculatePlaces(): void
    {
        app(RecalculateArrivalResultPlacesAction::class)($this->getOwnerRecord()->getKey());
    }
}
