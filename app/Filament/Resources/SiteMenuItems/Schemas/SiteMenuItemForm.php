<?php

namespace App\Filament\Resources\SiteMenuItems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SiteMenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Название')
                    ->required(),
                TextInput::make('href')
                    ->label('Ссылка')
                    ->required()
                    ->helperText('Например: /news или /contacts'),
                Toggle::make('is_active')
                    ->label('Показывать')
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
