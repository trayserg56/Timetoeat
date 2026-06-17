<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Cooking = 'cooking';
    case Ready = 'ready';
    case Delivering = 'delivering';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Confirmed => 'Подтверждён',
            self::Cooking => 'Готовится',
            self::Ready => 'Готов',
            self::Delivering => 'Доставляется',
            self::Completed => 'Завершён',
            self::Cancelled => 'Отменён',
        };
    }
}
