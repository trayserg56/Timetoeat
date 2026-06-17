<?php

namespace App\Console\Commands;

use App\Services\TelegramOrderNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook
                            {url : Публичный HTTPS-URL вашего сайта, например https://example.com}
                            {--delete : Удалить зарегистрированный webhook}';

    protected $description = 'Зарегистрировать или удалить webhook Telegram-бота для получения нажатий кнопок';

    public function handle(TelegramOrderNotifier $notifier): int
    {
        $botToken = $notifier->botToken();

        if (! $botToken) {
            $this->error('Токен Telegram-бота не задан. Укажите его в Настройках сайта или в .env (TELEGRAM_BOT_TOKEN).');

            return self::FAILURE;
        }

        if ($this->option('delete')) {
            return $this->deleteWebhook($botToken);
        }

        return $this->setWebhook($botToken, $this->argument('url'));
    }

    protected function setWebhook(string $botToken, string $siteUrl): int
    {
        $secret = app(TelegramOrderNotifier::class)->webhookSecret();

        if (! $secret) {
            $this->error('Секрет webhook не задан. Укажите его в Настройках сайта → Telegram-бот → «Секрет webhook».');

            return self::FAILURE;
        }

        $webhookUrl = rtrim($siteUrl, '/').'/telegram/orders/webhook/'.$secret;

        $this->info("Регистрирую webhook: {$webhookUrl}");

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                    'url' => $webhookUrl,
                    'allowed_updates' => ['callback_query'],
                    'drop_pending_updates' => true,
                ])
                ->throw()
                ->json();

            if (data_get($response, 'ok')) {
                $this->info('✅ Webhook успешно зарегистрирован.');
                $this->line('   '.data_get($response, 'description', ''));

                return self::SUCCESS;
            }

            $this->error('Telegram вернул ошибку: '.data_get($response, 'description', 'неизвестная ошибка'));

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Ошибка запроса: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function deleteWebhook(string $botToken): int
    {
        $this->info('Удаляю webhook...');

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$botToken}/deleteWebhook", [
                    'drop_pending_updates' => true,
                ])
                ->throw()
                ->json();

            if (data_get($response, 'ok')) {
                $this->info('✅ Webhook удалён.');

                return self::SUCCESS;
            }

            $this->error('Telegram вернул ошибку: '.data_get($response, 'description', 'неизвестная ошибка'));

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Ошибка запроса: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
