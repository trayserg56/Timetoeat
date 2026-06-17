<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramWebAppConfigurator
{
    public function __construct(
        private TelegramOrderNotifier $telegramOrderNotifier,
    ) {}

    public function botToken(): ?string
    {
        return $this->telegramOrderNotifier->botToken();
    }

    public function webAppUrl(?string $override = null): ?string
    {
        $url = $override
            ?: config('services.telegram.web_app_url')
            ?: config('app.url');

        $url = is_string($url) ? rtrim(trim($url), '/') : '';

        if ($url === '' || ! str_starts_with($url, 'https://')) {
            return null;
        }

        return $url;
    }

    public function setMenuButton(string $url, string $text = 'Заказать'): bool
    {
        $botToken = $this->botToken();

        if (! $botToken) {
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$botToken}/setChatMenuButton", [
                    'menu_button' => [
                        'type' => 'web_app',
                        'text' => $text,
                        'web_app' => [
                            'url' => $url,
                        ],
                    ],
                ])
                ->throw()
                ->json();

            return data_get($response, 'ok') === true;
        } catch (\Throwable $exception) {
            Log::warning('Failed to configure Telegram Web App menu button.', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function resetMenuButton(): bool
    {
        $botToken = $this->botToken();

        if (! $botToken) {
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$botToken}/setChatMenuButton", [
                    'menu_button' => [
                        'type' => 'commands',
                    ],
                ])
                ->throw()
                ->json();

            return data_get($response, 'ok') === true;
        } catch (\Throwable $exception) {
            Log::warning('Failed to reset Telegram menu button.', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function menuButtonInfo(): ?array
    {
        $botToken = $this->botToken();

        if (! $botToken) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$botToken}/getChatMenuButton")
                ->throw()
                ->json();

            return data_get($response, 'ok') ? data_get($response, 'result') : null;
        } catch (\Throwable $exception) {
            Log::warning('Failed to fetch Telegram menu button info.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
