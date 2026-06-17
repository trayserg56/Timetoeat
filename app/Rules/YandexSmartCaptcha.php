<?php

namespace App\Rules;

use App\Services\YandexSmartCaptchaVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YandexSmartCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $verifier = app(YandexSmartCaptchaVerifier::class);

        if (! $verifier->isEnabled()) {
            return;
        }

        if (! is_string($value) || trim($value) === '') {
            $fail('Не удалось пройти проверку безопасности. Попробуйте ещё раз.');

            return;
        }

        if (! $verifier->verify(trim($value), request()->ip())) {
            $fail('Не удалось пройти проверку безопасности. Попробуйте ещё раз.');
        }
    }
}
