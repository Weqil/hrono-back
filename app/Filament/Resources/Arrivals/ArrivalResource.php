<?php

namespace App\Filament\Resources\Arrivals;

use App\Filament\Resources\Arrivals\RelationManagers\ResultsRelationManager;
use App\Filament\Resources\Arrivals\Pages\CreateArrival;
use App\Filament\Resources\Arrivals\Pages\EditArrival;
use App\Filament\Resources\Arrivals\Pages\ListArrivals;
use App\Filament\Resources\Arrivals\Schemas\ArrivalForm;
use App\Filament\Resources\Arrivals\Tables\ArrivalsTable;
use App\Models\Arrival;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArrivalResource extends Resource
{
    protected static ?string $model = Arrival::class;

    protected static ?string $navigationLabel = 'Заезды';

    protected static ?string $modelLabel = 'заезд';

    protected static ?string $pluralModelLabel = 'Заезды';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    public static function form(Schema $schema): Schema
    {
        return ArrivalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArrivalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ResultsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArrivals::route('/'),
            'create' => CreateArrival::route('/create'),
            'edit' => EditArrival::route('/{record}/edit'),
        ];
    }
}
