<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'telegram_username' => ['required', 'regex:/^@[A-Za-z0-9_]{5,32}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        $telegramUsername = $this->input('telegram_username');

        if (is_string($phone)) {
            $digits = preg_replace('/\D+/', '', $phone);

            if (strlen($digits) === 10) {
                $digits = '7'.$digits;
            }

            if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
                $digits = '7'.substr($digits, 1);
            }

            if (strlen($digits) === 11 && str_starts_with($digits, '7')) {
                $phone = '+'.$digits;
            }
        }

        if (! is_string($telegramUsername)) {
            $this->merge([
                'phone' => $phone,
            ]);

            return;
        }

        $normalized = preg_replace('/\s+/', '', trim($telegramUsername));

        if ($normalized !== '' && ! str_starts_with($normalized, '@')) {
            $normalized = '@'.$normalized;
        }

        $this->merge([
            'phone' => $phone,
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
