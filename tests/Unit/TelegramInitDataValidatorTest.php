<?php

namespace Tests\Unit;

use App\Services\TelegramInitDataValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TelegramInitDataFactory;
use Tests\TestCase;

class TelegramInitDataValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_token', 'test-bot-token');
    }

    public function test_it_validates_signed_telegram_init_data(): void
    {
        $initData = TelegramInitDataFactory::make([
            'id' => 424242,
            'first_name' => 'Иван',
            'last_name' => 'Петров',
            'username' => 'ivan_test',
        ]);

        $validator = app(TelegramInitDataValidator::class);

        $this->assertTrue($validator->isValid($initData));

        $parsed = $validator->parse($initData);

        $this->assertSame(424242, $parsed['id']);
        $this->assertSame('Иван', $parsed['first_name']);
        $this->assertSame('Петров', $parsed['last_name']);
        $this->assertSame('ivan_test', $parsed['username']);
        $this->assertSame('@ivan_test', $validator->resolveTelegramUsername($parsed));
    }

    public function test_it_rejects_tampered_init_data(): void
    {
        $initData = TelegramInitDataFactory::make([
            'id' => 424242,
            'first_name' => 'Иван',
        ]);

        $this->assertFalse(app(TelegramInitDataValidator::class)->isValid($initData.'&extra=1'));
    }
}
