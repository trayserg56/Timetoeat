<?php

namespace Tests\Feature\Auth;

use App\Models\MealSet;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_access_profile(): void
    {
        $response = $this->from('/news')->post('/register', [
            'name' => 'Новый пользователь',
            'email' => 'new@example.com',
            'phone' => '+79990000002',
            'telegram_username' => 'new_user_telegram',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/news');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'phone' => '+79990000002',
            'telegram_username' => '@new_user_telegram',
        ]);

        $this->get('/profile')->assertOk();
    }

    public function test_user_can_login_and_stay_on_same_page(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $this->from('/news')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/news');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Обновлённое имя',
                'email' => 'updated@example.com',
                'phone' => '+79990000003',
                'telegram_username' => 'updated_username',
            ])
            ->assertRedirect('/profile/settings');

        $user->refresh();

        $this->assertSame('Обновлённое имя', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('+79990000003', $user->phone);
        $this->assertSame('@updated_username', $user->telegram_username);
    }

    public function test_user_can_repeat_order(): void
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
                        'quantity' => 2,
                    ],
                ],
            ]);

        $order = Order::query()->firstOrFail();

        $this->actingAs($user)
            ->from('/profile/orders')
            ->post("/profile/orders/{$order->id}/repeat")
            ->assertRedirect('/profile/orders')
            ->assertSessionHas('repeat_order.items.0', [
                'type' => 'meal_set',
                'id' => $mealSet->id,
                'quantity' => 2,
            ]);
    }

    public function test_user_can_view_own_order_details(): void
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

        $this->actingAs($user)
            ->get("/profile/orders/{$order->id}")
            ->assertOk();

        $this->actingAs($anotherUser)
            ->get("/profile/orders/{$order->id}")
            ->assertNotFound();
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/news')
                ->post('/login', [
                    'email' => 'login@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertSessionHasErrors('email');
        }

        $this->from('/news')
            ->post('/login', [
                'email' => 'login@example.com',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
