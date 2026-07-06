<?php

namespace App\Filament\Resources\ArrivalResults\Schemas;

use App\Filament\Support\RaceTimeForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArrivalResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('arrival_id')
                    ->relationship('arrival', 'name')
                    ->required(),
                TextInput::make('place')
                    ->label('Место')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('server_race_id')
                    ->required()
                    ->numeric(),
                TextInput::make('total_laps')
                    ->required()
                    ->numeric(),
                RaceTimeForm::totalTimeMsInput(),
                RaceTimeForm::bestLapTimeMsInput(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('surname')
                    ->required()
                    ->default(''),
                TextInput::make('patronymic')
                    ->required()
                    ->default(''),
                TextInput::make('start_number')
                    ->required()
                    ->numeric(),
                TextInput::make('tag_id')
                    ->required()
                    ->default(''),
                TextInput::make('grade')
                    ->required()
                    ->default(''),
                TextInput::make('command')
                    ->required()
                    ->default(''),
            ]);
    }
}
