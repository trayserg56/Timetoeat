<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\MaxOrderNotifier;
use App\Services\TelegramOrderNotifier;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $freshOrder = $order->fresh(['deliveryGroups.items.components']);

        app(TelegramOrderNotifier::class)->syncOrderMessage($freshOrder);
        app(MaxOrderNotifier::class)->syncOrderMessage($freshOrder);
    }
}
