<?php

namespace Tests\Feature;

use App\Enums\OrderSourceChannel;
use App\Models\MealSet;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TelegramInitDataFactory;
use Tests\TestCase;

class TelegramWebAppAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 12:00:00', 'Europe/Moscow'));
        config()->set('services.telegram.bot_token', 'test-bot-token');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_guest_is_authenticated_from_valid_telegram_init_data(): void
    {
        $initData = TelegramInitDataFactory::make([
            'id' => 987654321,
            'first_name' => 'Анна',
            'last_name' => 'Смирнова',
            'username' => 'anna_food',
        ]);

        $response = $this->postJson('/auth/telegram/webapp', [
            'init_data' => $initData,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertAuthenticated();

        $user = User::query()->where('telegram_id', 987654321)->first();

        $this->assertNotNull($user);
        $this->assertSame('Анна Смирнова', $user->name);
        $this->assertSame('@anna_food', $user->telegram_username);
        $this->assertSame('tg+987654321@telegram.local', $user->email);
    }

    public function test_invalid_init_data_is_rejected(): void
    {
        $response = $this->postJson('/auth/telegram/webapp', [
            'init_data' => 'user=%7B%22id%22%3A1%7D&auth_date=1&hash=invalid',
        ]);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_existing_user_is_linked_by_telegram_username(): void
    {
        $existingUser = User::factory()->create([
            'telegram_username' => '@linked_user',
            'email' => 'linked@example.com',
        ]);

        $initData = TelegramInitDataFactory::make([
            'id' => 555001,
            'first_name' => 'Linked',
            'username' => 'linked_user',
        ]);

        $this->postJson('/auth/telegram/webapp', [
            'init_data' => $initData,
        ])->assertOk();

        $this->assertSame($existingUser->id, Auth::id());
        $this->assertSame(555001, $existingUser->fresh()->telegram_id);
    }

    public function test_order_from_telegram_webapp_is_marked_with_source_channel(): void
    {
        Storage::fake('local');
        Http::fake();
        $this->seed();

        $mealSet = MealSet::query()->firstOrFail();
        $initData = TelegramInitDataFactory::make([
            'id' => 123123123,
            'first_name' => 'Telegram',
            'username' => 'tg_customer',
        ]);

        $this->postJson('/auth/telegram/webapp', [
            'init_data' => $initData,
        ])->assertOk();

        $response = $this->post('/orders', [
            'customer_name' => 'Telegram Клиент',
            'customer_phone' => '8 (999) 111-22-33',
            'customer_telegram_username' => 'tg_customer',
            'delivery_address' => 'ул. Telegram, 1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'telegram_init_data' => $initData,
            'items' => [
                [
                    'type' => 'meal_set',
                    'id' => $mealSet->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect('/');

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame(OrderSourceChannel::TelegramWebApp, $order->source_channel);
        $this->assertNotNull($order->user_id);
    }
}
