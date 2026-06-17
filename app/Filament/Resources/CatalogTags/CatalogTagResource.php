<?php

namespace App\Filament\Resources\CatalogTags;

use App\Filament\Resources\CatalogTags\Pages\CreateCatalogTag;
use App\Filament\Resources\CatalogTags\Pages\EditCatalogTag;
use App\Filament\Resources\CatalogTags\Pages\ListCatalogTags;
use App\Filament\Resources\CatalogTags\Schemas\CatalogTagForm;
use App\Filament\Resources\CatalogTags\Tables\CatalogTagsTable;
use App\Models\CatalogTag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CatalogTagResource extends Resource
{
    protected static ?string $model = CatalogTag::class;

    protected static ?string $modelLabel = 'тег каталога';

    protected static ?string $pluralModelLabel = 'теги каталога';

    protected static ?string $navigationLabel = 'Теги каталога';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return CatalogTagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CatalogTagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogTags::route('/'),
            'create' => CreateCatalogTag::route('/create'),
            'edit' => EditCatalogTag::route('/{record}/edit'),
        ];
    }
}
