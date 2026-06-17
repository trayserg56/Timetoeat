<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Доставка')
                    ->schema([
                        TextInput::make('delivery_price')
                            ->label('Стоимость доставки')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('коп.')
                            ->helperText('80 ₽ указываются как 8000 копеек.'),
                        TextInput::make('free_delivery_meal_set_quantity')
                            ->label('Бесплатная доставка от количества наборов')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('delivery_interval')
                            ->label('Интервал доставки')
                            ->required(),
                        TextInput::make('order_cutoff_time')
                            ->label('Приём заказов до')
                            ->required()
                            ->placeholder('00:00'),
                    ])
                    ->columns(2),
                Section::make('Оплата')
                    ->schema([
                        TextInput::make('payment_phone')
                            ->label('Номер для оплаты')
                            ->required(),
                        TextInput::make('payment_recipient')
                            ->label('Получатель')
                            ->required(),
                        TextInput::make('payment_banks')
                            ->label('Банки')
                            ->required(),
                        Textarea::make('payment_instruction')
                            ->label('Инструкция по оплате')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Требования к заказу')
                    ->schema([
                        Textarea::make('address_instruction')
                            ->label('Что указать в адресе')
                            ->required()
                            ->rows(3),
                        Textarea::make('phone_instruction')
                            ->label('Требование к телефону')
                            ->required()
                            ->rows(3),
                    ])
                    ->columns(2),
                Section::make('Контакты и футер')
                    ->schema([
                        TextInput::make('contact_phone')
                            ->label('Телефон')
                            ->required(),
                        TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        TextInput::make('contact_telegram')
                            ->label('Telegram')
                            ->required(),
                        Textarea::make('contact_address')
                            ->label('Адрес / зона доставки')
                            ->required()
                            ->rows(3),
                        Textarea::make('contact_schedule')
                            ->label('График / условия')
                            ->required()
                            ->rows(3),
                        Textarea::make('footer_description')
                            ->label('Описание в футере')
                            ->required()
                            ->rows(3),
                    ])
                    ->columns(2),
                Section::make('Telegram-бот')
                    ->description('Настройки уведомлений о заказах и webhook для кнопок в Telegram.')
                    ->schema([
                        Placeholder::make('telegram_saved_status')
                            ->label('Текущие настройки')
                            ->content(function (?\App\Models\SiteSetting $record): string {
                                if (! $record) {
                                    return 'Сохранённых настроек пока нет.';
                                }

                                $parts = [];

                                if (filled($record->telegram_bot_token)) {
                                    $parts[] = 'Токен сохранён';
                                }

                                if (filled($record->telegram_orders_chat_id)) {
                                    $parts[] = 'Chat ID: '.$record->telegram_orders_chat_id;
                                }

                                if (filled($record->telegram_webhook_secret)) {
                                    $parts[] = 'Секрет webhook сохранён';
                                }

                                return $parts !== []
                                    ? implode(' · ', $parts)
                                    : 'В админке Telegram не задан — используются значения из .env (если есть).';
                            })
                            ->columnSpanFull(),
                        TextInput::make('telegram_bot_token')
                            ->label('Токен бота')
                            ->password()
                            ->afterStateHydrated(fn ($component) => $component->state(''))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Поле намеренно пустое при редактировании (как пароль). Оставьте пустым, чтобы не менять. Иначе — TELEGRAM_BOT_TOKEN из .env.'),
                        TextInput::make('telegram_orders_chat_id')
                            ->label('Chat ID группы/чата')
                            ->helperText('Например: -5363983169. Если поле пустое, будет использоваться TELEGRAM_ORDERS_CHAT_ID из .env.'),
                        TextInput::make('telegram_webhook_secret')
                            ->label('Секрет webhook')
                            ->password()
                            ->afterStateHydrated(fn ($component) => $component->state(''))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Оставьте пустым, чтобы сохранить текущий секрет. Если секрет не задан в админке, будет использоваться TELEGRAM_WEBHOOK_SECRET из .env.'),
                    ])
                    ->columns(2),
                Section::make('MAX-бот')
                    ->description('Настройки уведомлений о заказах и webhook для кнопок в мессенджере MAX.')
                    ->schema([
                        TextInput::make('max_bot_token')
                            ->label('Токен бота MAX')
                            ->password()
                            ->afterStateHydrated(fn ($component) => $component->state(''))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Оставьте пустым, чтобы сохранить текущий токен. Если токен не задан в админке, будет использоваться MAX_BOT_TOKEN из .env.'),
                        TextInput::make('max_orders_chat_id')
                            ->label('Chat ID группы/чата MAX')
                            ->helperText('Числовой ID чата или группы. Если поле пустое, будет использоваться MAX_ORDERS_CHAT_ID из .env.'),
                        TextInput::make('max_webhook_secret')
                            ->label('Секрет webhook MAX')
                            ->password()
                            ->afterStateHydrated(fn ($component) => $component->state(''))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Произвольная строка. Добавьте её в URL webhook при регистрации в dev.max.ru: /max/orders/webhook/{секрет}'),
                    ])
                    ->columns(2),
            ]);
    }
}
