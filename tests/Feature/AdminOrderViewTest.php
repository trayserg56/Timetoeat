<?php

namespace Tests\Feature;

use App\Models\MealSet;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrderViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_order_details(): void
    {
        Storage::fake('local');

        $this->seed();

        $user = User::factory()->create();
        $mealSet = MealSet::query()->firstOrFail();

        $this->actingAs($user)
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

        $admin = User::query()
            ->where('email', 'admin@example.com')
            ->firstOrFail();
        $order = Order::query()->firstOrFail();

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Открыть чек')
            ->assertSee('Посмотреть');
    }

    public function test_non_admin_cannot_open_order_receipt(): void
    {
        Storage::fake('local');

        $this->seed();

        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $mealSet = MealSet::query()->firstOrFail();

        $this->actingAs($user)
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

        $order = Order::query()->firstOrFail();

        $this->actingAs($anotherUser)
            ->get(route('admin.orders.receipt', $order))
            ->assertNotFound();
    }
}
