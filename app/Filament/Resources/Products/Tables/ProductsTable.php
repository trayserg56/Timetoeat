<?php

namespace App\Filament\Resources\Products\Tables;

use Carbon\CarbonImmutable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),
                TextColumn::make('menu_dates')
                    ->label('Даты меню')
                    ->formatStateUsing(fn (?array $state): string => collect($state)
                        ->pluck('date')
                        ->filter()
                        ->map(fn (string $date): string => CarbonImmutable::parse($date)->format('d.m.Y'))
                        ->implode(', '))
                    ->placeholder('Всегда')
                    ->wrap(),
                TextColumn::make('tags.name')
                    ->label('Теги')
                    ->badge()
                    ->separator(','),
                ImageColumn::make('image_path'),
                TextColumn::make('weight_grams')
                    ->label('Вес, г')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_available')
                    ->label('Доступно')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
