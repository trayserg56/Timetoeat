<?php

namespace Tests\Unit;

use App\Support\ContactLinks;
use PHPUnit\Framework\TestCase;

class ContactLinksTest extends TestCase
{
    public function test_phone_href_normalizes_russian_number(): void
    {
        $this->assertSame('tel:+79990000001', ContactLinks::phoneHref('+7 (999) 000-00-01'));
    }

    public function test_mailto_href(): void
    {
        $this->assertSame('mailto:hello@example.com', ContactLinks::mailtoHref('hello@example.com'));
    }

    public function test_telegram_href_prefers_admin_url(): void
    {
        $this->assertSame(
            'https://t.me/custom',
            ContactLinks::telegramHref('https://t.me/custom', '@food_delivery'),
        );
    }

    public function test_telegram_href_falls_back_to_username(): void
    {
        $this->assertSame('https://t.me/food_delivery', ContactLinks::telegramHref(null, '@food_delivery'));
    }
}
