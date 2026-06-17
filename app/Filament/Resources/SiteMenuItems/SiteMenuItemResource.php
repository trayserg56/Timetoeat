<?php

namespace App\Filament\Resources\SiteMenuItems;

use App\Filament\Resources\SiteMenuItems\Pages\CreateSiteMenuItem;
use App\Filament\Resources\SiteMenuItems\Pages\EditSiteMenuItem;
use App\Filament\Resources\SiteMenuItems\Pages\ListSiteMenuItems;
use App\Filament\Resources\SiteMenuItems\Schemas\SiteMenuItemForm;
use App\Filament\Resources\SiteMenuItems\Tables\SiteMenuItemsTable;
use App\Models\SiteMenuItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SiteMenuItemResource extends Resource
{
    protected static ?string $model = SiteMenuItem::class;

    protected static ?string $modelLabel = 'пункт меню';

    protected static ?string $pluralModelLabel = 'меню сайта';

    protected static ?string $navigationLabel = 'Меню сайта';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    public static function form(Schema $schema): Schema
    {
        return SiteMenuItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteMenuItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSiteMenuItems::route('/'),
            'create' => CreateSiteMenuItem::route('/create'),
            'edit' => EditSiteMenuItem::route('/{record}/edit'),
        ];
    }
}
