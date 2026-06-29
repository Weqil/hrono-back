<?php

namespace App\Filament\Resources\ArrivalResultLaps;

use App\Filament\Resources\ArrivalResultLaps\Pages\CreateArrivalResultLap;
use App\Filament\Resources\ArrivalResultLaps\Pages\EditArrivalResultLap;
use App\Filament\Resources\ArrivalResultLaps\Pages\ListArrivalResultLaps;
use App\Filament\Resources\ArrivalResultLaps\Schemas\ArrivalResultLapForm;
use App\Filament\Resources\ArrivalResultLaps\Tables\ArrivalResultLapsTable;
use App\Models\ArrivalResultLap;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArrivalResultLapResource extends Resource
{
    protected static ?string $model = ArrivalResultLap::class;

    protected static ?string $navigationLabel = 'Круги';

    protected static ?string $modelLabel = 'круг';

    protected static ?string $pluralModelLabel = 'Круги';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return ArrivalResultLapForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArrivalResultLapsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArrivalResultLaps::route('/'),
            'create' => CreateArrivalResultLap::route('/create'),
            'edit' => EditArrivalResultLap::route('/{record}/edit'),
        ];
    }
}
