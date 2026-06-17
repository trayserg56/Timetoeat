<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;

class OrderWebhookConfirmation
{
    public static function canConfirm(Order $order): bool
    {
        return $order->status === OrderStatus::New;
    }
}
