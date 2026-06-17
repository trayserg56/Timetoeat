<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\OrderItemComponent;
use App\Models\OrderDeliveryGroup;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Заказ')
                    ->schema([
                        TextEntry::make('number')
                            ->label('Номер')
                            ->weight('bold'),
                        TextEntry::make('public_id')
                            ->label('Публичный ID')
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->badge(),
                        TextEntry::make('payment_status')
                            ->label('Оплата')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Создан')
                            ->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(3),
                Section::make('Клиент и доставка')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Пользователь')
                            ->placeholder('Гость'),
                        TextEntry::make('customer_name')
                            ->label('Имя'),
                        TextEntry::make('customer_phone')
                            ->label('Телефон')
                            ->copyable(),
                        TextEntry::make('customer_telegram_username')
                            ->label('Telegram')
                            ->copyable(),
                        TextEntry::make('source_channel')
                            ->label('Источник')
                            ->badge(),
                        TextEntry::make('customer_email')
                            ->label('Email')
                            ->placeholder('-'),
                        TextEntry::make('delivery_date')
                            ->label('Дата доставки')
                            ->date('d.m.Y')
                            ->placeholder('-'),
                        TextEntry::make('delivery_interval')
                            ->label('Окно доставки')
                            ->placeholder('-'),
                        TextEntry::make('delivery_address')
                            ->label('Адрес')
                            ->formatStateUsing(fn ($record): string => $record->deliveryGroups->count() > 1
                                ? 'Несколько адресов: '.$record->deliveryGroups->count()
                                : (string) $record->delivery_address)
                            ->columnSpanFull(),
                        TextEntry::make('customer_comment')
                            ->label('Комментарий')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Позиции')
                    ->schema([
                        RepeatableEntry::make('deliveryGroups')
                            ->label('Адреса доставки')
                            ->schema([
                                TextEntry::make('delivery_address')
                                    ->label('Адрес')
                                    ->weight('bold')
                                    ->columnSpanFull(),
                                TextEntry::make('customer_comment')
                                    ->label('Комментарий')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                                TextEntry::make('subtotal')
                                    ->label('Позиции')
                                    ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                                TextEntry::make('delivery_price')
                                    ->label('Доставка')
                                    ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                                TextEntry::make('total')
                                    ->label('Итого')
                                    ->formatStateUsing(fn (int $state): string => self::formatMoney($state))
                                    ->weight('bold'),
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Название')
                                            ->weight('bold'),
                                        TextEntry::make('quantity')
                                            ->label('Количество'),
                                        TextEntry::make('unit_price')
                                            ->label('Цена')
                                            ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                                        TextEntry::make('total_price')
                                            ->label('Сумма')
                                            ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                                        TextEntry::make('product_ingredients')
                                            ->label('Состав блюда')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                        TextEntry::make('components')
                                            ->label('Состав набора')
                                            ->state(fn ($record) => $record->components)
                                            ->formatStateUsing(fn ($state): HtmlString|string => self::formatComponents($state))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->contained(false),
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->visible(fn ($record): bool => $record->deliveryGroups->isEmpty())
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Название')
                                    ->weight('bold'),
                                TextEntry::make('quantity')
                                    ->label('Количество'),
                                TextEntry::make('unit_price')
                                    ->label('Цена')
                                    ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                                TextEntry::make('total_price')
                                    ->label('Сумма')
                                    ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                                TextEntry::make('product_ingredients')
                                    ->label('Состав блюда')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                                TextEntry::make('components')
                                    ->label('Состав набора')
                                    ->state(fn ($record) => $record->components)
                                    ->formatStateUsing(fn ($state): HtmlString|string => self::formatComponents($state))
                                    ->columnSpanFull(),
                            ])
                            ->columns(4),
                    ]),
                Section::make('Оплата')
                    ->schema([
                        TextEntry::make('subtotal')
                            ->label('Позиции')
                            ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                        TextEntry::make('delivery_price')
                            ->label('Доставка')
                            ->formatStateUsing(fn (int $state): string => self::formatMoney($state)),
                        TextEntry::make('total')
                            ->label('Итого')
                            ->formatStateUsing(fn (int $state): string => self::formatMoney($state))
                            ->weight('bold'),
                        TextEntry::make('receipt_uploaded_at')
                            ->label('Чек загружен')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('paid_at')
                            ->label('Оплачен')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Чек перевода')
                    ->schema([
                        TextEntry::make('receipt_path')
                            ->label('Файл')
                            ->formatStateUsing(fn (?string $state): string => $state ? self::receiptFileLabel($state) : 'Чек не прикреплен')
                            ->weight('bold'),
                        Actions::make([
                            Action::make('viewReceipt')
                                ->label('Посмотреть')
                                ->button()
                                ->modalHeading('Чек перевода')
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Закрыть')
                                ->modalWidth('7xl')
                                ->modalContent(fn ($record): HtmlString => self::receiptModalContent($record?->receipt_path, $record))
                                ->visible(fn ($record): bool => filled($record?->receipt_path)),
                        ]),
                    ])
                    ->visible(fn ($record): bool => filled($record?->receipt_path))
                    ->columns(1),
            ]);
    }

    protected static function formatMoney(int $kopecks): string
    {
        return number_format($kopecks / 100, 0, ',', ' ').' ₽';
    }

    protected static function formatComponents($components): HtmlString|string
    {
        if (! $components) {
            return '-';
        }

        if ($components instanceof OrderItemComponent) {
            return new HtmlString(self::formatComponent($components));
        }

        if ($components instanceof OrderDeliveryGroup) {
            return '-';
        }

        if ($components instanceof EloquentCollection || $components instanceof Collection) {
            if ($components->isEmpty()) {
                return '-';
            }

            return new HtmlString(
                $components
                    ->map(fn ($component): string => self::formatComponent($component))
                    ->implode('<br>'),
            );
        }

        if ($components instanceof Model) {
            return '-';
        }

        return (string) $components;
    }

    protected static function formatComponent(OrderItemComponent $component): string
    {
        return e($component->name).' × '.$component->quantity;
    }

    protected static function receiptUrl(?string $path, $record = null): ?string
    {
        if (! $path || ! $record) {
            return null;
        }

        return $record->receiptUrl();
    }

    protected static function receiptFileLabel(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return 'Открыть чек'.($extension ? ' ('.strtoupper($extension).')' : '');
    }

    protected static function isReceiptImage(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    protected static function receiptModalContent(?string $path, $record = null): HtmlString
    {
        $url = self::receiptUrl($path, $record);

        if (! $url) {
            return new HtmlString('<p>Чек не прикреплен.</p>');
        }

        $escapedUrl = e($url);

        if (self::isReceiptImage($path)) {
            return new HtmlString(<<<HTML
                <div style="display:flex;justify-content:center;background:#f5f5f4;border-radius:24px;padding:16px;">
                    <img src="{$escapedUrl}" alt="Чек перевода" style="max-width:100%;max-height:75vh;object-fit:contain;border-radius:18px;" />
                </div>
                HTML);
        }

        return new HtmlString(<<<HTML
            <iframe src="{$escapedUrl}" title="Чек перевода" style="width:100%;height:75vh;border:0;border-radius:18px;background:#f5f5f4;"></iframe>
            HTML);
    }
}
