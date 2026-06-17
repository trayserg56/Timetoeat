<?php

namespace App\Filament\Resources\SiteSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SiteSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_price')
                    ->label('Доставка')
                    ->money('RUB', divideBy: 100),
                TextColumn::make('free_delivery_meal_set_quantity')
                    ->label('Бесплатно от наборов'),
                TextColumn::make('delivery_interval')
                    ->label('Интервал доставки'),
                TextColumn::make('order_cutoff_time')
                    ->label('Заказы до'),
                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
