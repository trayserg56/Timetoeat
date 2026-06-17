<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\MaxOrderNotifier;
use App\Services\TelegramOrderNotifier;

class OrderObserver
{
    public function updated(Order $order): void
    {
        $statusChanged = $order->wasChanged('status');
        $paymentStatusChanged = $order->wasChanged('payment_status');

        if (! $statusChanged && ! $paymentStatusChanged) {
            return;
        }

        $freshOrder = $order->fresh(['deliveryGroups.items.components']);
        $telegramNotifier = app(TelegramOrderNotifier::class);

        if ($statusChanged) {
            $previousStatus = $order->getOriginal('status');
            $previousStatus = $previousStatus instanceof OrderStatus
                ? $previousStatus
                : OrderStatus::from($order->getRawOriginal('status'));

            $telegramNotifier->sendStatusChangeNotice($freshOrder, $previousStatus);
        }

        if ($paymentStatusChanged) {
            $previousPaymentStatus = $order->getOriginal('payment_status');
            $previousPaymentStatus = $previousPaymentStatus instanceof PaymentStatus
                ? $previousPaymentStatus
                : PaymentStatus::from($order->getRawOriginal('payment_status'));

            $telegramNotifier->sendPaymentStatusChangeNotice($freshOrder, $previousPaymentStatus);
        }

        $telegramNotifier->syncOrderMessage($freshOrder);
        app(MaxOrderNotifier::class)->syncOrderMessage($freshOrder);
    }
}
