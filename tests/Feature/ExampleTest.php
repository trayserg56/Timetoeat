<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
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

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_home_page_includes_product_without_menu_dates(): void
    {
        $this->seed();

        $product = Product::query()->firstOrFail();
        $product->update(['menu_dates' => null]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where(
                    'extraProducts',
                    fn ($products) => collect($products)->contains('id', $product->id),
                ));
    }

    public function test_home_page_includes_product_with_tomorrow_among_multiple_menu_dates(): void
    {
        $this->seed();

        $product = Product::query()->firstOrFail();
        $product->update([
            'menu_dates' => [
                ['date' => now('Europe/Moscow')->addDay()->toDateString()],
                ['date' => now('Europe/Moscow')->addDays(2)->toDateString()],
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where(
                    'extraProducts',
                    fn ($products) => collect($products)->contains('id', $product->id),
                ));
    }

    public function test_home_page_includes_reusable_catalog_tags(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('mealSets.0.tags.0.name', 'Новинка')
                ->where('mealSets.0.items.0.product.ingredients', 'Курица, домашняя лапша, картофель, морковь, лук, зелень.')
                ->where('extraProducts.0.ingredients', 'Курица, домашняя лапша, картофель, морковь, лук, зелень.')
                ->where('extraProducts.0.tags.0.name', 'Новинка')
                ->where('checkoutSettings.delivery_price', 8000)
                ->where('checkoutSettings.free_delivery_meal_set_quantity', 5));
    }

    public function test_home_page_includes_banner_without_menu_date(): void
    {
        $this->seed();

        $banner = Banner::query()->firstOrFail();
        $banner->update(['menu_date' => null]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where(
                    'banners',
                    fn ($banners) => collect($banners)->contains('id', $banner->id),
                ));
    }
}
