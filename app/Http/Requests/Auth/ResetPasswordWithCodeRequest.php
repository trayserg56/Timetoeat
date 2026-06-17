<?php

namespace App\Http\Requests\Auth;

use App\Rules\YandexSmartCaptcha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordWithCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'smart-token' => [new YandexSmartCaptcha],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $code = $this->input('code');

        $payload = [];

        if (is_string($email)) {
            $payload['email'] = mb_strtolower(trim($email));
        }

        if (is_string($code)) {
            $payload['code'] = preg_replace('/\D+/', '', $code) ?? $code;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}
