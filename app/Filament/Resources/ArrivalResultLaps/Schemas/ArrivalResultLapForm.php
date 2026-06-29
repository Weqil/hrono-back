<?php

namespace App\Filament\Resources\ArrivalResultLaps\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArrivalResultLapForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('arrival_result_id')
                    ->relationship('arrivalResult', 'name')
                    ->required(),
                TextInput::make('lap_number')
                    ->required()
                    ->numeric(),
                TextInput::make('lap_time_ms')
                    ->required()
                    ->numeric(),
                TextInput::make('timestamp_ms')
                    ->required()
                    ->numeric(),
                TextInput::make('position_on_lap')
                    ->required()
                    ->numeric(),
                Toggle::make('is_manual')
                    ->required(),
            ]);
    }
}
