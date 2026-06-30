<?php

namespace App\Filament\Resources\ArrivalTypes;

use App\Filament\Resources\ArrivalTypes\Pages\ListArrivalTypes;
use App\Filament\Resources\ArrivalTypes\Tables\ArrivalTypesTable;
use App\Models\ArrivalType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArrivalTypeResource extends Resource
{
    protected static ?string $model = ArrivalType::class;

    protected static ?string $navigationLabel = 'Типы заездов';

    protected static ?string $modelLabel = 'тип заезда';

    protected static ?string $pluralModelLabel = 'Типы заездов';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ArrivalTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArrivalTypes::route('/'),
        ];
    }
}
