<?php

namespace App\Console\Commands;

use App\Services\TelegramWebAppConfigurator;
use Illuminate\Console\Command;

class TelegramSetWebAppCommand extends Command
{
    protected $signature = 'telegram:set-web-app
                            {url? : Публичный HTTPS-URL Mini App, по умолчанию APP_URL или TELEGRAM_WEB_APP_URL}
                            {--text=Заказать : Текст кнопки меню в боте}
                            {--reset : Вернуть стандартную кнопку меню (список команд)}';

    protected $description = 'Настроить кнопку меню Telegram-бота для открытия сайта в WebView (Mini App)';

    public function handle(TelegramWebAppConfigurator $configurator): int
    {
        $botToken = $configurator->botToken();

        if (! $botToken) {
            $this->error('Токен Telegram-бота не задан. Укажите его в Настройках сайта или в .env (TELEGRAM_BOT_TOKEN).');

            return self::FAILURE;
        }

        if ($this->option('reset')) {
            if ($configurator->resetMenuButton()) {
                $this->info('✅ Кнопка меню возвращена к стандартному виду.');

                return self::SUCCESS;
            }

            $this->error('Не удалось сбросить кнопку меню.');

            return self::FAILURE;
        }

        $url = $configurator->webAppUrl($this->argument('url'));

        if (! $url) {
            $this->error('Нужен HTTPS-URL. Укажите аргумент url или задайте APP_URL=https://ваш-домен в .env.');

            return self::FAILURE;
        }

        $text = (string) $this->option('text');

        $this->info("Настраиваю Mini App: {$url}");
        $this->line("Текст кнопки: {$text}");

        if ($configurator->setMenuButton($url, $text)) {
            $this->info('✅ Кнопка меню бота настроена. Откройте бота в Telegram — внизу появится кнопка для открытия сайта.');

            return self::SUCCESS;
        }

        $this->error('Telegram не принял настройку Mini App. Проверьте, что домен доступен по HTTPS.');

        return self::FAILURE;
    }
}
