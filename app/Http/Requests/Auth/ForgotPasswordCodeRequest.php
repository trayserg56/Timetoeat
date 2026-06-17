<?php

namespace App\Http\Requests\Auth;

use App\Rules\YandexSmartCaptcha;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'smart-token' => [new YandexSmartCaptcha],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (! is_string($email)) {
            return;
        }

        $this->merge([
            'email' => mb_strtolower(trim($email)),
        ]);
    }
}
