<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Номер')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('Клиент')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('customer_telegram_username')
                    ->label('Telegram')
                    ->searchable(),
                TextColumn::make('source_channel')
                    ->label('Источник')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state?->getLabel() ?? 'Сайт')
                    ->toggleable(),
                TextColumn::make('customer_email')
                    ->searchable(),
                TextColumn::make('delivery_date')
                    ->label('Дата доставки')
                    ->date()
                    ->sortable(),
                TextColumn::make('delivery_interval')
                    ->label('Окно доставки')
                    ->searchable(),
                TextColumn::make('receipt_path')
                    ->label('Чек')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Открыть' : '-')
                    ->url(fn ($record): ?string => $record->receiptUrl())
                    ->openUrlInNewTab(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->getLabel()),
                TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->getLabel()),
                TextColumn::make('total')
                    ->label('Сумма')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
