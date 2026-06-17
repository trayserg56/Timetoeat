<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TelegramWebAppAuthService
{
    public function __construct(
        private TelegramInitDataValidator $initDataValidator,
    ) {}

    public function authenticate(?string $initData): ?User
    {
        $telegramUser = $this->initDataValidator->parse($initData);

        if ($telegramUser === null) {
            return null;
        }

        $currentUser = Auth::user();

        if ($currentUser instanceof User) {
            if ($currentUser->telegram_id === $telegramUser['id']) {
                $this->syncProfile($currentUser, $telegramUser);

                return $currentUser;
            }

            return $currentUser;
        }

        $user = $this->findOrCreateUser($telegramUser);
        Auth::login($user, remember: true);

        return $user;
    }

    protected function findOrCreateUser(array $telegramUser): User
    {
        $existingByTelegramId = User::query()
            ->where('telegram_id', $telegramUser['id'])
            ->first();

        if ($existingByTelegramId) {
            $this->syncProfile($existingByTelegramId, $telegramUser);

            return $existingByTelegramId;
        }

        $telegramUsername = $this->initDataValidator->resolveTelegramUsername($telegramUser);

        $existingByUsername = User::query()
            ->whereNull('telegram_id')
            ->where('telegram_username', $telegramUsername)
            ->first();

        if ($existingByUsername) {
            $existingByUsername->forceFill([
                'telegram_id' => $telegramUser['id'],
            ]);
            $this->syncProfile($existingByUsername, $telegramUser);
            $existingByUsername->save();

            return $existingByUsername;
        }

        return User::query()->create([
            'telegram_id' => $telegramUser['id'],
            'name' => $this->initDataValidator->resolveDisplayName($telegramUser),
            'email' => $this->placeholderEmail($telegramUser['id']),
            'telegram_username' => $telegramUsername,
            'password' => Hash::make(Str::password(32)),
        ]);
    }

    protected function syncProfile(User $user, array $telegramUser): void
    {
        $updates = [];

        $displayName = $this->initDataValidator->resolveDisplayName($telegramUser);

        if ($displayName !== '' && ($user->name === '' || str_starts_with((string) $user->email, 'tg+'))) {
            $updates['name'] = $displayName;
        }

        $telegramUsername = $this->initDataValidator->resolveTelegramUsername($telegramUser);

        if ($telegramUsername !== '' && (! filled($user->telegram_username) || str_starts_with((string) $user->telegram_username, '@tg'))) {
            $updates['telegram_username'] = $telegramUsername;
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }
    }

    protected function placeholderEmail(int $telegramId): string
    {
        return 'tg+'.$telegramId.'@telegram.local';
    }
}
