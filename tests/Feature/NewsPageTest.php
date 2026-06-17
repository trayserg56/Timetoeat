<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_index_is_available(): void
    {
        $this->seed();

        $response = $this->get('/news');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('News/Index')
                ->has('news', 3)
                ->where('news.2.slug', 'menu-na-zavtra-launch'));
    }

    public function test_news_detail_is_available(): void
    {
        $this->seed();

        $response = $this->get('/news/menu-na-zavtra-launch');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('News/Show')
                ->where('news.slug', 'menu-na-zavtra-launch')
                ->where('news.title', 'Мы запускаем меню на завтра')
                ->where('news.content', 'Теперь на сайте каждый день публикуется новое меню на следующий день. Это помогает заранее собрать наборы, дополнительные блюда и не путать актуальные позиции между днями.'));
    }
}
