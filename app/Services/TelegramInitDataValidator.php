<?php

namespace App\Services;

class TelegramInitDataValidator
{
    public function __construct(
        private TelegramOrderNotifier $telegramOrderNotifier,
    ) {}

    public function isValid(?string $initData, ?int $maxAgeSeconds = 86400): bool
    {
        return $this->parse($initData, $maxAgeSeconds) !== null;
    }

    /**
     * @return array{
     *     id: int,
     *     first_name: string,
     *     last_name: ?string,
     *     username: ?string,
     *     language_code: ?string,
     *     auth_date: int,
     * }|null
     */
    public function parse(?string $initData, ?int $maxAgeSeconds = 86400): ?array
    {
        $initData = trim((string) $initData);

        if ($initData === '') {
            return null;
        }

        $botToken = $this->telegramOrderNotifier->botToken();

        if (! is_string($botToken) || $botToken === '') {
            return null;
        }

        parse_str($initData, $params);

        if (! is_array($params) || ! isset($params['hash']) || ! is_string($params['hash'])) {
            return null;
        }

        $receivedHash = $params['hash'];
        unset($params['hash']);

        ksort($params);

        $dataCheckString = collect($params)
            ->map(fn (mixed $value, string $key): string => $key.'='.$value)
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculatedHash, $receivedHash)) {
            return null;
        }

        $authDate = isset($params['auth_date']) ? (int) $params['auth_date'] : 0;

        if ($authDate <= 0) {
            return null;
        }

        if ($maxAgeSeconds !== null && (time() - $authDate) > $maxAgeSeconds) {
            return null;
        }

        if (! isset($params['user']) || ! is_string($params['user']) || $params['user'] === '') {
            return null;
        }

        $user = json_decode($params['user'], true);

        if (! is_array($user) || ! isset($user['id'])) {
            return null;
        }

        $telegramId = (int) $user['id'];

        if ($telegramId <= 0) {
            return null;
        }

        return [
            'id' => $telegramId,
            'first_name' => trim((string) ($user['first_name'] ?? '')),
            'last_name' => filled($user['last_name'] ?? null) ? trim((string) $user['last_name']) : null,
            'username' => filled($user['username'] ?? null) ? trim((string) $user['username']) : null,
            'language_code' => filled($user['language_code'] ?? null) ? trim((string) $user['language_code']) : null,
            'auth_date' => $authDate,
        ];
    }

    public function resolveTelegramUsername(array $telegramUser): string
    {
        $username = trim((string) ($telegramUser['username'] ?? ''));

        if ($username !== '') {
            return str_starts_with($username, '@') ? $username : '@'.$username;
        }

        return '@tg'.$telegramUser['id'];
    }

    public function resolveDisplayName(array $telegramUser): string
    {
        $parts = array_filter([
            trim((string) ($telegramUser['first_name'] ?? '')),
            trim((string) ($telegramUser['last_name'] ?? '')),
        ]);

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return 'Telegram '.$telegramUser['id'];
    }
}
