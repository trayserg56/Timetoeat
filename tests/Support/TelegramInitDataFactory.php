<?php

namespace Tests\Support;

class TelegramInitDataFactory
{
    /**
     * @param  array<string, mixed>  $user
     */
    public static function make(array $user, ?int $authDate = null, string $botToken = 'test-bot-token'): string
    {
        $authDate ??= time();

        $params = [
            'auth_date' => (string) $authDate,
            'user' => json_encode($user, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        ksort($params);

        $dataCheckString = collect($params)
            ->map(fn (string $value, string $key): string => $key.'='.$value)
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $params['hash'] = hash_hmac('sha256', $dataCheckString, $secretKey);

        return http_build_query($params);
    }
}
