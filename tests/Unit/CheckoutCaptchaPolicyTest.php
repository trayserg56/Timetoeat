<?php

namespace Tests\Unit;

use App\Support\CheckoutCaptchaPolicy;
use Illuminate\Http\Request;
use Tests\TestCase;

class CheckoutCaptchaPolicyTest extends TestCase
{
    public function test_captcha_is_not_required_for_telegram_user_agent(): void
    {
        config()->set('services.yandex_captcha.server_key', 'test-server-key');

        $request = Request::create('/orders', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Telegram/10.0',
        ]);

        $this->assertFalse(app(CheckoutCaptchaPolicy::class)->isRequired($request));
    }
}
