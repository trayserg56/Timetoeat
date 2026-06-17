<?php

namespace App\Filament\Resources\CatalogTags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CatalogTagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
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
