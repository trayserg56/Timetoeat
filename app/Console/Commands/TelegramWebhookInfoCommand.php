<?php

namespace App\Console\Commands;

use App\Services\TelegramOrderNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramWebhookInfoCommand extends Command
{
    protected $signature = 'telegram:webhook-info';

    protected $description = 'Показать информацию о зарегистрированном webhook Telegram-бота';

    public function handle(TelegramOrderNotifier $notifier): int
    {
        $botToken = $notifier->botToken();

        if (! $botToken) {
            $this->error('Токен Telegram-бота не задан.');

            return self::FAILURE;
        }

        try {
            $response = Http::timeout(15)
                ->get("https://api.telegram.org/bot{$botToken}/getWebhookInfo")
                ->throw()
                ->json();

            $info = data_get($response, 'result', []);

            $this->table(
                ['Параметр', 'Значение'],
                [
                    ['URL', data_get($info, 'url') ?: '(не задан)'],
                    ['Pending updates', data_get($info, 'pending_update_count', 0)],
                    ['Последняя ошибка', data_get($info, 'last_error_message') ?: '—'],
                    ['Дата ошибки', data_get($info, 'last_error_date') ? date('Y-m-d H:i:s', data_get($info, 'last_error_date')) : '—'],
                ],
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Ошибка запроса: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
