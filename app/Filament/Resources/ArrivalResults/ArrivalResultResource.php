<?php

namespace App\Filament\Resources\ArrivalResults;

use App\Filament\Resources\ArrivalResults\RelationManagers\LapsRelationManager;
use App\Filament\Resources\ArrivalResults\Pages\CreateArrivalResult;
use App\Filament\Resources\ArrivalResults\Pages\EditArrivalResult;
use App\Filament\Resources\ArrivalResults\Pages\ListArrivalResults;
use App\Filament\Resources\ArrivalResults\Schemas\ArrivalResultForm;
use App\Filament\Resources\ArrivalResults\Tables\ArrivalResultsTable;
use App\Models\ArrivalResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ArrivalResultResource extends Resource
{
    protected static ?string $model = ArrivalResult::class;

    protected static ?string $navigationLabel = 'Результаты';

    protected static ?string $modelLabel = 'результат';

    protected static ?string $pluralModelLabel = 'Результаты';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    public static function form(Schema $schema): Schema
    {
        return ArrivalResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArrivalResultsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LapsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArrivalResults::route('/'),
            'create' => CreateArrivalResult::route('/create'),
            'edit' => EditArrivalResult::route('/{record}/edit'),
        ];
    }
}
