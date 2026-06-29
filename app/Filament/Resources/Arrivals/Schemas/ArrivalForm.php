<?php

namespace App\Filament\Resources\Arrivals\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArrivalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                TextInput::make('moto_race_id')
                    ->label('ID гонки (Moto)')
                    ->required()
                    ->numeric(),
                TextInput::make('time')
                    ->label('Время')
                    ->required(),
                TextInput::make('round_min_time')
                    ->label('Мин. время круга (сек)')
                    ->required()
                    ->numeric()
                    ->default(0),
                TagsInput::make('arrival_grades')
                    ->label('Классы')
                    ->placeholder('Добавить класс'),
                Toggle::make('finished')
                    ->label('Завершён'),
                TextInput::make('local_arrival_id')
                    ->label('Локальный ID')
                    ->numeric(),
                DateTimePicker::make('finished_at')
                    ->label('Время завершения'),
                DateTimePicker::make('moto_stream_opened_at')
                    ->label('Трансляция открыта'),
                DateTimePicker::make('moto_stream_closed_at')
                    ->label('Трансляция закрыта'),
            ]);
    }
}
