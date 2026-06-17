<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOrderWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_secret(): void
    {
        config()->set('services.telegram.webhook_secret', 'valid-secret');

        $this->postJson('/telegram/orders/webhook/wrong-secret', [])
            ->assertNotFound();
    }

    public function test_webhook_cannot_confirm_cancelled_order(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.orders_chat_id', '-5363983169');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $order = Order::query()->create([
            'public_id' => '77f0bf16-34c8-46f5-b3d8-23b0f0b8f620',
            'number' => 'FD-20260613-0386',
            'customer_name' => 'Клиент',
            'customer_phone' => '+79990000001',
            'customer_telegram_username' => '@client',
            'delivery_address' => 'Тестовый адрес',
            'delivery_date' => now()->toDateString(),
            'delivery_interval' => '09:00-12:00 МСК',
            'status' => OrderStatus::Cancelled,
            'payment_status' => 'receipt_uploaded',
            'payment_method' => 'bank_transfer',
            'subtotal' => 230000,
            'delivery_price' => 8000,
            'total' => 238000,
            'telegram_chat_id' => '-5363983169',
            'telegram_message_id' => 44,
        ]);

        $this->postJson('/telegram/orders/webhook/telegram-secret', [
            'callback_query' => [
                'id' => 'callback-1',
                'data' => 'confirm:77f0bf16-34c8-46f5-b3d8-23b0f0b8f620',
                'message' => [
                    'message_id' => 44,
                    'chat' => [
                        'id' => -5363983169,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }
}
