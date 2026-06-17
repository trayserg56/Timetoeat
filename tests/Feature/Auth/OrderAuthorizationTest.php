<?php

namespace Tests\Feature\Auth;

use App\Models\MealSet;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_repeat_someone_else_order(): void
    {
        Storage::fake('local');
        $this->seed();

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $mealSet = MealSet::query()->firstOrFail();

        $this->actingAs($owner)
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

        $this->actingAs($intruder)
            ->post("/profile/orders/{$order->id}/repeat")
            ->assertNotFound();
    }

    public function test_regular_user_cannot_access_filament_admin(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}
