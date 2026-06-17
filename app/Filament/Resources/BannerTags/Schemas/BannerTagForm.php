<?php

namespace App\Filament\Resources\BannerTags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerTagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                TextInput::make('slug')
                    ->label('Адрес')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->required()
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->required()
                    ->default(0),
            ]);
    }
}
