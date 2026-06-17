<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'telegram_username' => ['required', 'regex:/^@[A-Za-z0-9_]{5,32}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $telegramUsername = $this->input('telegram_username');

        if (! is_string($telegramUsername)) {
            return;
        }

        $normalized = preg_replace('/\s+/', '', trim($telegramUsername));

        if ($normalized !== '' && ! str_starts_with($normalized, '@')) {
            $normalized = '@'.$normalized;
        }

        $this->merge([
            'telegram_username' => $normalized,
        ]);
    }

    public function messages(): array
    {
        return [
            'telegram_username.required' => 'Укажите Telegram-ник.',
            'telegram_username.regex' => 'Укажите Telegram-ник в формате @username.',
        ];
    }
}
