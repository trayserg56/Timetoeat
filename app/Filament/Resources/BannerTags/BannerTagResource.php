<?php

namespace App\Filament\Resources\BannerTags;

use App\Filament\Resources\BannerTags\Pages\CreateBannerTag;
use App\Filament\Resources\BannerTags\Pages\EditBannerTag;
use App\Filament\Resources\BannerTags\Pages\ListBannerTags;
use App\Filament\Resources\BannerTags\Schemas\BannerTagForm;
use App\Filament\Resources\BannerTags\Tables\BannerTagsTable;
use App\Models\BannerTag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BannerTagResource extends Resource
{
    protected static ?string $model = BannerTag::class;

    protected static ?string $modelLabel = 'тег баннера';

    protected static ?string $pluralModelLabel = 'теги баннеров';

    protected static ?string $navigationLabel = 'Теги баннеров';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return BannerTagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannerTagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBannerTags::route('/'),
            'create' => CreateBannerTag::route('/create'),
            'edit' => EditBannerTag::route('/{record}/edit'),
        ];
    }
}
