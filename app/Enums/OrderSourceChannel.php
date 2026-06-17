<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderSourceChannel: string implements HasLabel
{
    case Website = 'website';
    case TelegramWebApp = 'telegram_webapp';

    public function getLabel(): string
    {
        return match ($this) {
            self::Website => 'Сайт',
            self::TelegramWebApp => 'Telegram WebApp',
        };
    }
}
