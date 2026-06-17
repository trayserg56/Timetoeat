<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;

class OrderTelegramWorkflow
{
    public static function targetStatus(string $action): ?OrderStatus
    {
        return match ($action) {
            'confirm' => OrderStatus::Confirmed,
            'cooking' => OrderStatus::Cooking,
            'ready' => OrderStatus::Ready,
            'delivering' => OrderStatus::Delivering,
            'complete' => OrderStatus::Completed,
            'cancel' => OrderStatus::Cancelled,
            default => null,
        };
    }

    public static function canApply(Order $order, OrderStatus $targetStatus): bool
    {
        return match ($targetStatus) {
            OrderStatus::Confirmed => $order->status === OrderStatus::New,
            OrderStatus::Cooking => $order->status === OrderStatus::Confirmed,
            OrderStatus::Ready => $order->status === OrderStatus::Cooking,
            OrderStatus::Delivering => $order->status === OrderStatus::Ready,
            OrderStatus::Completed => $order->status === OrderStatus::Delivering,
            OrderStatus::Cancelled => in_array($order->status, [
                OrderStatus::New,
                OrderStatus::Confirmed,
                OrderStatus::Cooking,
                OrderStatus::Ready,
            ], true),
            default => false,
        };
    }

    public static function successMessage(OrderStatus $targetStatus): string
    {
        return match ($targetStatus) {
            OrderStatus::Confirmed => 'Заказ подтверждён.',
            OrderStatus::Cooking => 'Заказ передан на кухню.',
            OrderStatus::Ready => 'Заказ готов.',
            OrderStatus::Delivering => 'Заказ передан в доставку.',
            OrderStatus::Completed => 'Заказ завершён.',
            OrderStatus::Cancelled => 'Заказ отменён.',
            default => 'Статус заказа обновлён.',
        };
    }
}
