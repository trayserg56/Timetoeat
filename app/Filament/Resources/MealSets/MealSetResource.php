<?php

namespace App\Filament\Resources\MealSets;

use App\Filament\Resources\MealSets\Pages\CreateMealSet;
use App\Filament\Resources\MealSets\Pages\EditMealSet;
use App\Filament\Resources\MealSets\Pages\ListMealSets;
use App\Filament\Resources\MealSets\Schemas\MealSetForm;
use App\Filament\Resources\MealSets\Tables\MealSetsTable;
use App\Models\MealSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MealSetResource extends Resource
{
    protected static ?string $model = MealSet::class;

    protected static ?string $modelLabel = 'набор';

    protected static ?string $pluralModelLabel = 'наборы';

    protected static ?string $navigationLabel = 'Наборы';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MealSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MealSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMealSets::route('/'),
            'create' => CreateMealSet::route('/create'),
            'edit' => EditMealSet::route('/{record}/edit'),
        ];
    }
}
