<?php

namespace Tests\Feature;

use App\Enums\OrderSourceChannel;
use App\Models\MealSet;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 12:00:00', 'Europe/Moscow'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_customer_can_place_order_with_meal_set_and_product(): void
    {
        Storage::fake('local');
        Http::fake();

        $this->seed();
        $user = User::factory()->create();
        $this->actingAs($user);

        $mealSet = MealSet::query()->with('items.product')->firstOrFail();
        $product = Product::query()->firstOrFail();

        $response = $this
            ->withHeaders([
                'CF-Connecting-IP' => '203.0.113.10',
                'X-Forwarded-For' => '203.0.113.10, 172.19.0.1',
                'User-Agent' => 'OrderPlacementTest/1.0',
            ])
            ->post('/orders', [
                'customer_name' => 'Иван',
                'customer_phone' => '8 (999) 000-00-00',
                'customer_telegram_username' => 'ivan_customer',
                'customer_email' => 'ivan@example.com',
                'delivery_address' => 'ул. Тестовая, дом 1',
                'customer_comment' => 'Позвонить за 10 минут',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
                'items' => [
                    [
                        'type' => 'meal_set',
                        'id' => $mealSet->id,
                        'quantity' => 2,
                    ],
                    [
                        'type' => 'product',
                        'id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        $order = Order::query()->with('items.components')->first();

        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('Иван', $order->customer_name);
        $this->assertSame('+79990000000', $order->customer_phone);
        $this->assertSame('@ivan_customer', $order->customer_telegram_username);
        $this->assertSame('203.0.113.10', $order->source_ip);
        $this->assertSame('203.0.113.10, 172.19.0.1', $order->source_forwarded_for);
        $this->assertSame('OrderPlacementTest/1.0', $order->source_user_agent);
        $this->assertSame(OrderSourceChannel::Website, $order->source_channel);
        $this->assertSame(
            CarbonImmutable::now('Europe/Moscow')->addDay()->toDateString(),
            $order->delivery_date?->toDateString() ?? $order->delivery_date,
        );
        $this->assertSame('09:00-12:00 МСК', $order->delivery_interval);
        $this->assertSame('@ivan_customer', $user->fresh()->telegram_username);
        $this->assertCount(2, $order->items);
        $this->assertSame(
            ($mealSet->price * 2) + $product->price + 8000,
            $order->total,
        );
        $this->assertSame(8000, $order->delivery_price);

        $mealSetOrderItem = $order->items->firstWhere('type', 'meal_set');
        $productOrderItem = $order->items->firstWhere('type', 'product');

        $this->assertNotNull($mealSetOrderItem);
        $this->assertSame($product->ingredients, $productOrderItem->product_ingredients);
        $this->assertCount($mealSet->items->count(), $mealSetOrderItem->components);
        $this->assertSame(
            $mealSet->items->first()->quantity * 2,
            $mealSetOrderItem->components->first()->quantity,
        );

        Storage::disk('local')->assertExists($order->receipt_path);
    }

    public function test_telegram_client_can_place_order_without_captcha_when_captcha_is_enabled(): void
    {
        Storage::fake('local');
        Http::fake();

        config()->set('services.yandex_captcha.server_key', 'test-server-key');
        config()->set('services.yandex_captcha.client_key', 'test-client-key');

        $this->seed();

        $mealSet = MealSet::query()->firstOrFail();

        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Telegram/10.0',
            ])
            ->post('/orders', [
                'customer_name' => 'Иван',
                'customer_phone' => '8 (999) 000-00-00',
                'customer_telegram_username' => 'ivan_customer',
                'delivery_address' => 'ул. Тестовая, дом 1',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
                'items' => [
                    [
                        'type' => 'meal_set',
                        'id' => $mealSet->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertRedirect('/');
        $this->assertNotNull(Order::query()->first());
    }

    public function test_order_is_sent_to_telegram_after_successful_checkout(): void
    {
        Storage::fake('local');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.orders_chat_id', '-1001234567890');

        $this->seed();

        $mealSet = MealSet::query()->with('items.product')->firstOrFail();
        $product = Product::query()->firstOrFail();

        $this->post('/orders', [
            'customer_name' => 'Иван',
            'customer_phone' => '8 (999) 000-00-00',
            'customer_telegram_username' => 'ivan_customer',
            'customer_email' => 'ivan@example.com',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'order_groups' => [
                [
                    'delivery_address' => 'ул. Первая, дом 1',
                    'customer_comment' => 'Позвонить у шлагбаума',
                    'items' => [
                        [
                            'type' => 'meal_set',
                            'id' => $mealSet->id,
                            'quantity' => 1,
                        ],
                    ],
                ],
                [
                    'delivery_address' => 'ул. Вторая, дом 2',
                    'customer_comment' => 'Оставить на ресепшене',
                    'items' => [
                        [
                            'type' => 'product',
                            'id' => $product->id,
                            'quantity' => 2,
                        ],
                    ],
                ],
            ],
        ])->assertRedirect('/');

        Http::assertSent(function ($request) use ($mealSet, $product): bool {
            $data = $request->data();

            return $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
                && $data['chat_id'] === '-1001234567890'
                && str_contains($data['text'], 'Новый заказ')
                && str_contains($data['text'], '#НОВЫЙ')
                && str_contains($data['text'], 'Статус: <b>Новый</b>')
                && str_contains($data['text'], 'Иван')
                && str_contains($data['text'], '@ivan_customer')
                && str_contains($data['text'], 'ул. Первая, дом 1')
                && str_contains($data['text'], 'ул. Вторая, дом 2')
                && str_contains($data['text'], $mealSet->name)
                && str_contains($data['text'], $product->name)
                && str_contains((string) $data['reply_markup'], 'Подтвердить');
        });
    }

    public function test_order_uses_telegram_settings_from_site_settings(): void
    {
        Storage::fake('local');

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', null);
        config()->set('services.telegram.orders_chat_id', null);

        $this->seed();

        SiteSetting::current()->update([
            'telegram_bot_token' => 'site-settings-bot-token',
            'telegram_orders_chat_id' => '-100777',
        ]);

        $mealSet = MealSet::query()->firstOrFail();

        $this->post('/orders', [
            'customer_name' => 'Иван',
            'customer_phone' => '8 (999) 000-00-00',
            'customer_telegram_username' => 'ivan_customer',
            'delivery_address' => 'ул. Тестовая, дом 1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'items' => [
                [
                    'type' => 'meal_set',
                    'id' => $mealSet->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertRedirect('/');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/botsite-settings-bot-token/sendMessage'
            && $request->data()['chat_id'] === '-100777');
    }

    public function test_customer_cannot_place_order_after_cutoff_time(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 15:00:00', 'Europe/Moscow'));

        try {
            Storage::fake('local');

            $this->seed();
            SiteSetting::current()->update(['order_cutoff_time' => '10:00']);

            $mealSet = MealSet::query()->firstOrFail();

            $response = $this->post('/orders', [
                'customer_name' => 'Иван',
                'customer_phone' => '8 (999) 000-00-00',
                'customer_telegram_username' => 'ivan_customer',
                'delivery_address' => 'ул. Тестовая, дом 1',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
                'items' => [
                    [
                        'type' => 'meal_set',
                        'id' => $mealSet->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

            $response->assertSessionHasErrors('items');
            $this->assertDatabaseCount('orders', 0);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_delivery_is_free_for_five_or_more_meal_sets(): void
    {
        Storage::fake('local');

        $this->seed();

        $mealSet = MealSet::query()->firstOrFail();

        $this->post('/orders', [
            'customer_name' => 'Иван',
            'customer_phone' => '8 (999) 000-00-00',
            'customer_telegram_username' => 'ivan_customer',
            'delivery_address' => 'ул. Тестовая, дом 1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'items' => [
                [
                    'type' => 'meal_set',
                    'id' => $mealSet->id,
                    'quantity' => 5,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $this->assertSame(0, $order->delivery_price);
        $this->assertSame($mealSet->price * 5, $order->total);
    }

    public function test_delivery_uses_site_settings(): void
    {
        Storage::fake('local');

        $this->seed();

        SiteSetting::current()->update([
            'delivery_price' => 12500,
            'free_delivery_meal_set_quantity' => 3,
            'delivery_interval' => '14:00-16:00',
        ]);

        $mealSet = MealSet::query()->firstOrFail();

        $this->post('/orders', [
            'customer_name' => 'Иван',
            'customer_phone' => '8 (999) 000-00-00',
            'customer_telegram_username' => 'ivan_customer',
            'delivery_address' => 'ул. Тестовая, дом 1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'items' => [
                [
                    'type' => 'meal_set',
                    'id' => $mealSet->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $this->assertSame(12500, $order->delivery_price);
        $this->assertSame(($mealSet->price * 2) + 12500, $order->total);
        $this->assertSame('14:00-16:00', $order->delivery_interval);
    }

    public function test_customer_can_split_cart_into_multiple_delivery_addresses(): void
    {
        Storage::fake('local');

        $this->seed();

        SiteSetting::current()->update([
            'delivery_price' => 5000,
            'free_delivery_meal_set_quantity' => 3,
        ]);

        $mealSet = MealSet::query()->firstOrFail();
        $product = Product::query()->firstOrFail();

        $response = $this->post('/orders', [
            'customer_name' => 'Иван',
            'customer_phone' => '8 (999) 000-00-00',
            'customer_telegram_username' => 'ivan_customer',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'order_groups' => [
                [
                    'delivery_address' => 'ул. Первая, дом 1',
                    'customer_comment' => 'Оставить у охраны',
                    'items' => [
                        [
                            'type' => 'meal_set',
                            'id' => $mealSet->id,
                            'quantity' => 2,
                        ],
                    ],
                ],
                [
                    'delivery_address' => 'ул. Вторая, дом 2',
                    'customer_comment' => 'Поднять на второй этаж',
                    'items' => [
                        [
                            'type' => 'meal_set',
                            'id' => $mealSet->id,
                            'quantity' => 1,
                        ],
                        [
                            'type' => 'product',
                            'id' => $product->id,
                            'quantity' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('order.delivery_groups_count', 2);

        $order = Order::query()->with('deliveryGroups.items')->firstOrFail();

        $this->assertSame('Несколько адресов: 2', $order->delivery_address);
        $this->assertSame(((2 * $mealSet->price) + 5000) + ($mealSet->price + ($product->price * 2) + 5000), $order->total);
        $this->assertSame((3 * $mealSet->price) + ($product->price * 2), $order->subtotal);
        $this->assertSame(10000, $order->delivery_price);
        $this->assertCount(2, $order->deliveryGroups);

        $firstGroup = $order->deliveryGroups->firstWhere('delivery_address', 'ул. Первая, дом 1');
        $secondGroup = $order->deliveryGroups->firstWhere('delivery_address', 'ул. Вторая, дом 2');

        $this->assertNotNull($firstGroup);
        $this->assertNotNull($secondGroup);
        $this->assertSame((2 * $mealSet->price) + 5000, $firstGroup->total);
        $this->assertSame($mealSet->price + ($product->price * 2) + 5000, $secondGroup->total);
        $this->assertCount(1, $firstGroup->items);
        $this->assertCount(2, $secondGroup->items);
    }

    public function test_customer_cannot_order_items_from_another_menu_date(): void
    {
        Storage::fake('local');

        $this->seed();

        $futureMealSet = MealSet::query()
            ->whereJsonContains(
                'menu_dates',
                CarbonImmutable::now('Europe/Moscow')->addDays(2)->toDateString(),
            )
            ->firstOrFail();

        $response = $this->post('/orders', [
            'customer_name' => 'Иван',
            'customer_phone' => '8 (999) 000-00-00',
            'customer_telegram_username' => 'ivan_customer',
            'delivery_address' => 'ул. Тестовая, дом 1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'items' => [
                [
                    'type' => 'meal_set',
                    'id' => $futureMealSet->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_order_product_without_menu_dates(): void
    {
        Storage::fake('local');

        $this->seed();

        $product = Product::query()->firstOrFail();
        $product->update(['menu_dates' => null]);

        $response = $this->post('/orders', [
            'customer_name' => 'Иван',
            'customer_phone' => '8 (999) 000-00-00',
            'customer_telegram_username' => 'ivan_customer',
            'delivery_address' => 'ул. Тестовая, дом 1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 128, 'application/pdf'),
            'items' => [
                [
                    'type' => 'product',
                    'id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $this->assertSame(8000, $order->delivery_price);
        $this->assertSame($product->price + 8000, $order->total);
        $this->assertDatabaseHas('order_items', [
            'type' => 'product',
            'purchasable_id' => $product->id,
            'name' => $product->name,
        ]);
    }
}
