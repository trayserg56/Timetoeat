<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_web_app_command_configures_menu_button(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('app.url', 'https://example.com');

        $this->artisan('telegram:set-web-app')
            ->expectsOutputToContain('https://example.com')
            ->assertSuccessful();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/setChatMenuButton'
            && data_get($request->data(), 'menu_button.type') === 'web_app'
            && data_get($request->data(), 'menu_button.web_app.url') === 'https://example.com'
            && data_get($request->data(), 'menu_button.text') === 'Заказать');
    }

    public function test_set_web_app_command_fails_without_https_url(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('app.url', 'http://example.com');

        $this->artisan('telegram:set-web-app')
            ->assertFailed();
    }
}
