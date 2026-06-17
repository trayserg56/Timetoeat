<?php

namespace App\Support;

use App\Models\User;
use App\Services\TelegramInitDataValidator;
use App\Services\YandexSmartCaptchaVerifier;
use Illuminate\Http\Request;

class CheckoutCaptchaPolicy
{
    public function __construct(
        private YandexSmartCaptchaVerifier $captchaVerifier,
        private TelegramInitDataValidator $telegramInitDataValidator,
    ) {}

    public function isRequired(Request $request): bool
    {
        if (! $this->captchaVerifier->isEnabled()) {
            return false;
        }

        if ($this->telegramInitDataValidator->isValid($request->input('telegram_init_data'))) {
            return false;
        }

        $user = $request->user();

        if ($user instanceof User && $user->telegram_id) {
            return false;
        }

        if ($this->isTelegramClientRequest($request)) {
            return false;
        }

        return true;
    }

    public function isTelegramClientRequest(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());

        return str_contains($userAgent, 'telegram');
    }
}
