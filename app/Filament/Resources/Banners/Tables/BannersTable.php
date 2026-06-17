<?php

namespace App\Filament\Resources\Banners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable(),
                TextColumn::make('mealSet.name')
                    ->label('Набор')
                    ->placeholder('Без привязки')
                    ->searchable(),
                TextColumn::make('menu_date')
                    ->label('Дата меню')
                    ->date('d.m.Y')
                    ->placeholder('Всегда')
                    ->sortable(),
                ImageColumn::make('image_path')
                    ->label('Изображение'),
                TextColumn::make('link_url')
                    ->label('Ссылка')
                    ->limit(40)
                    ->url(fn ($record) => $record->link_url, shouldOpenInNewTab: true)
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
