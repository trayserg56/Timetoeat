<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOrderStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_from_admin_updates_telegram_message_and_sends_notice(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.orders_chat_id', '-5363983169');

        $this->seed();

        $order = Order::query()->create([
            'public_id' => '77f0bf16-34c8-46f5-b3d8-23b0f0b8f620',
            'number' => 'FD-20260613-0386',
            'customer_name' => 'Администратор',
            'customer_phone' => '+79990000001',
            'customer_telegram_username' => '@food_admin',
            'delivery_address' => 'Тестовый адрес',
            'delivery_date' => now()->toDateString(),
            'delivery_interval' => '09:00-12:00 МСК',
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::ReceiptUploaded,
            'payment_method' => 'bank_transfer',
            'subtotal' => 230000,
            'delivery_price' => 8000,
            'total' => 238000,
            'telegram_chat_id' => '-5363983169',
            'telegram_message_id' => 44,
        ]);

        $order->update([
            'status' => OrderStatus::Cooking,
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
            && $request->data()['reply_to_message_id'] === 44
            && str_contains($request->data()['text'], 'Подтверждён → <b>Готовится</b>'));

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/editMessageText'
            && $request->data()['message_id'] === 44
            && str_contains($request->data()['text'], '👨‍🍳')
            && str_contains($request->data()['text'], 'Статус заказа: <b>Готовится</b>')
            && str_contains($request->data()['reply_markup'], '🍱 Готов'));
    }

    public function test_payment_status_change_sends_telegram_notice(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.orders_chat_id', '-5363983169');

        $this->seed();

        $order = Order::query()->create([
            'public_id' => '77f0bf16-34c8-46f5-b3d8-23b0f0b8f620',
            'number' => 'FD-20260613-0386',
            'customer_name' => 'Администратор',
            'customer_phone' => '+79990000001',
            'customer_telegram_username' => '@food_admin',
            'delivery_address' => 'Тестовый адрес',
            'delivery_date' => now()->toDateString(),
            'delivery_interval' => '09:00-12:00 МСК',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::ReceiptUploaded,
            'payment_method' => 'bank_transfer',
            'subtotal' => 230000,
            'delivery_price' => 8000,
            'total' => 238000,
            'telegram_chat_id' => '-5363983169',
            'telegram_message_id' => 44,
        ]);

        $order->update([
            'payment_status' => PaymentStatus::Paid,
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
            && str_contains($request->data()['text'], 'Чек загружен → <b>Оплачен</b>'));
    }

    public function test_webhook_can_move_order_through_kitchen_statuses(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.orders_chat_id', '-5363983169');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $this->seed();

        $order = Order::query()->create([
            'public_id' => '77f0bf16-34c8-46f5-b3d8-23b0f0b8f620',
            'number' => 'FD-20260613-0386',
            'customer_name' => 'Администратор',
            'customer_phone' => '+79990000001',
            'customer_telegram_username' => '@food_admin',
            'delivery_address' => 'Тестовый адрес',
            'delivery_date' => now()->toDateString(),
            'delivery_interval' => '09:00-12:00 МСК',
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::ReceiptUploaded,
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
                'data' => 'cooking:77f0bf16-34c8-46f5-b3d8-23b0f0b8f620',
                'message' => [
                    'message_id' => 44,
                    'chat' => ['id' => -5363983169],
                ],
            ],
        ])->assertOk();

        $this->assertSame(OrderStatus::Cooking, $order->fresh()->status);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/answerCallbackQuery'
            && $request->data()['text'] === 'Заказ передан на кухню.');
    }
}
