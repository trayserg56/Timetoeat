<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YandexSmartCaptchaVerifier
{
    private const VALIDATE_URL = 'https://smartcaptcha.cloud.yandex.ru/validate';

    public function isEnabled(): bool
    {
        return filled(config('services.yandex_captcha.server_key'));
    }

    public function verify(string $token, ?string $ip = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if ($token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VALIDATE_URL, array_filter([
                    'secret' => config('services.yandex_captcha.server_key'),
                    'token' => $token,
                    'ip' => $ip,
                ]))
                ->json();
        } catch (RequestException $exception) {
            Log::warning('Yandex SmartCaptcha validation request failed', [
                'message' => $exception->getMessage(),
            ]);

            return true;
        }

        return is_array($response) && ($response['status'] ?? null) === 'ok';
    }
}
