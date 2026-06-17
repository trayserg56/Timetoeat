<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('public_id')
                    ->required(),
                TextInput::make('number')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('customer_name')
                    ->required(),
                TextInput::make('customer_phone')
                    ->tel()
                    ->required(),
                TextInput::make('customer_telegram_username')
                    ->label('Telegram')
                    ->required(),
                TextInput::make('customer_email')
                    ->email(),
                Textarea::make('delivery_address')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('delivery_date'),
                TextInput::make('delivery_interval'),
                Textarea::make('customer_comment')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default('new')
                    ->required(),
                Select::make('payment_status')
                    ->options(PaymentStatus::class)
                    ->default('awaiting_receipt')
                    ->required(),
                TextInput::make('payment_method')
                    ->required()
                    ->default('bank_transfer'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('delivery_price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                Placeholder::make('receipt_file')
                    ->label('Чек оплаты')
                    ->content(fn ($record): HtmlString|string => $record?->receipt_path
                        ? new HtmlString(sprintf(
                            '<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#ea580c;font-weight:600;">Открыть файл</a>',
                            $record->receiptUrl(),
                        ))
                        : '-'),
                DateTimePicker::make('receipt_uploaded_at'),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
