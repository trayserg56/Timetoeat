<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramOrderWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_confirm_order_from_telegram_button(): void
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
            'customer_email' => 'admin@example.com',
            'delivery_address' => 'Тестовый адрес',
            'delivery_date' => now()->toDateString(),
            'delivery_interval' => '09:00-12:00 МСК',
            'status' => OrderStatus::New,
            'payment_status' => 'receipt_uploaded',
            'payment_method' => 'bank_transfer',
            'subtotal' => 230000,
            'delivery_price' => 8000,
            'total' => 238000,
            'telegram_chat_id' => '-5363983169',
            'telegram_message_id' => 44,
        ]);

        $response = $this->postJson('/telegram/orders/webhook/telegram-secret', [
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
        ]);

        $response->assertOk();

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/answerCallbackQuery'
            && $request->data()['callback_query_id'] === 'callback-1');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/editMessageText'
            && $request->data()['chat_id'] === '-5363983169'
            && $request->data()['message_id'] === 44
            && str_contains($request->data()['text'], 'Статус: Подтверждён')
            && str_contains($request->data()['reply_markup'], '✅ Подтверждён'));
    }

    public function test_webhook_ignores_callbacks_from_other_chat(): void
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
            'status' => OrderStatus::New,
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
                        'id' => -123,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_webhook_can_use_secret_from_site_settings(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', null);
        config()->set('services.telegram.orders_chat_id', null);
        config()->set('services.telegram.webhook_secret', null);

        $this->seed();

        SiteSetting::current()->update([
            'telegram_bot_token' => 'test-bot-token',
            'telegram_orders_chat_id' => '-5363983169',
            'telegram_webhook_secret' => 'site-secret',
        ]);

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
            'payment_status' => 'receipt_uploaded',
            'payment_method' => 'bank_transfer',
            'subtotal' => 230000,
            'delivery_price' => 8000,
            'total' => 238000,
            'telegram_chat_id' => '-5363983169',
            'telegram_message_id' => 44,
        ]);

        $this->postJson('/telegram/orders/webhook/site-secret', [
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

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
    }

    public function test_telegram_settings_are_encrypted_in_database(): void
    {
        $this->seed();

        $settings = SiteSetting::current();

        $settings->update([
            'telegram_bot_token' => 'plain-bot-token',
            'telegram_orders_chat_id' => '-100555',
            'telegram_webhook_secret' => 'plain-secret',
        ]);

        $rawSettings = DB::table('site_settings')->first();

        $this->assertNotSame('plain-bot-token', $rawSettings->telegram_bot_token);
        $this->assertNotSame('-100555', $rawSettings->telegram_orders_chat_id);
        $this->assertNotSame('plain-secret', $rawSettings->telegram_webhook_secret);
        $this->assertSame('plain-bot-token', $settings->fresh()->telegram_bot_token);
    }
}
