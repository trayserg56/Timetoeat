<?php

namespace Tests\Feature\Console;

use App\Models\MealSet;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignMenuDatesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_forward_dates_to_products_and_varied_dates_to_meal_sets(): void
    {
        $this->seed();

        $tomorrow = CarbonImmutable::now('Europe/Moscow')->addDay()->toDateString();

        $this->artisan('menu:assign-dates')
            ->assertSuccessful();

        $product = Product::query()->where('slug', 'chicken-soup')->firstOrFail();
        $productDates = collect($product->menu_dates)->pluck('date');

        $this->assertGreaterThanOrEqual(5, $productDates->count());
        $this->assertTrue($productDates->contains($tomorrow));

        $availableSet = MealSet::query()->where('slug', 'home-lunch')->firstOrFail();
        $unavailableSet = MealSet::query()->where('slug', 'light-lunch')->firstOrFail();

        $this->assertTrue(
            collect($availableSet->menu_dates)->pluck('date')->contains($tomorrow),
        );
        $this->assertFalse(
            collect($unavailableSet->menu_dates)->pluck('date')->contains($tomorrow),
        );
    }
}
