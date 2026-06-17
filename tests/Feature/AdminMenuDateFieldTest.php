<?php

namespace Tests\Feature;

use App\Models\MealSet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuDateFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_multiple_menu_dates_in_a_single_field(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $product = Product::query()->firstOrFail();
        $mealSet = MealSet::query()->firstOrFail();

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Даты меню')
            ->assertSee('type="date"', false);

        $this->actingAs($admin)
            ->get("/admin/meal-sets/{$mealSet->id}/edit")
            ->assertOk()
            ->assertSee('Даты меню')
            ->assertSee('type="date"', false);
    }
}
