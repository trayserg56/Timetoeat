<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerformanceHintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_preloads_hero_image_and_does_not_block_on_telegram_script(): void
    {
        Storage::fake('public');

        $this->seed();

        Banner::query()->update([
            'image_url' => 'https://images.pexels.com/photos/1640771/pexels-photo-1640771.jpeg?auto=compress&cs=tinysrgb&w=1200',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('rel="preload"', false);
        $response->assertSee('images.pexels.com/photos/1640771', false);
        $response->assertSee('rel="preconnect"', false);
        $response->assertDontSee('telegram.org/js/telegram-web-app.js', false);
    }

    public function test_news_show_page_preloads_featured_image(): void
    {
        $news = News::query()->create([
            'title' => 'Тестовая новость',
            'slug' => 'test-news',
            'excerpt' => 'Краткое описание',
            'content' => 'Полный текст новости.',
            'image_url' => 'https://images.pexels.com/photos/999999/pexels-photo-999999.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'published_at' => now()->subDay(),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('news.show', $news->slug));

        $response->assertOk();
        $response->assertSee('images.pexels.com/photos/999999', false);
        $response->assertSee('rel="preload"', false);
    }
}
