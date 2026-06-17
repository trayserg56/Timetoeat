<?php

namespace Tests\Feature;

use App\Services\YandexSmartCaptchaVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YandexSmartCaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_valid_captcha_when_enabled(): void
    {
        Config::set('services.yandex_captcha.server_key', 'test-server-key');
        Config::set('services.yandex_captcha.client_key', 'test-client-key');

        Http::fake([
            'smartcaptcha.cloud.yandex.ru/validate' => Http::response([
                'status' => 'failed',
                'message' => '',
            ]),
        ]);

        $this->from('/')
            ->post('/login', [
                'email' => 'user@example.com',
                'password' => 'password',
                'smart-token' => 'invalid-token',
            ])
            ->assertSessionHasErrors('smart-token');
    }

    public function test_login_passes_when_captcha_verification_succeeds(): void
    {
        Config::set('services.yandex_captcha.server_key', 'test-server-key');
        Config::set('services.yandex_captcha.client_key', 'test-client-key');

        Http::fake([
            'smartcaptcha.cloud.yandex.ru/validate' => Http::response([
                'status' => 'ok',
                'message' => '',
                'host' => 'example.com',
            ]),
        ]);

        $user = \App\Models\User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->from('/')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
                'smart-token' => 'valid-token',
            ])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_captcha_is_skipped_when_server_key_is_not_configured(): void
    {
        Config::set('services.yandex_captcha.server_key', null);
        Config::set('services.yandex_captcha.client_key', null);

        $this->assertFalse(app(YandexSmartCaptchaVerifier::class)->isEnabled());

        $user = \App\Models\User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->from('/')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }
}
