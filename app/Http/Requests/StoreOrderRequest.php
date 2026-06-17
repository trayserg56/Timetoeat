<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_phone' => $this->normalizeRussianPhone($this->input('customer_phone')),
            'customer_telegram_username' => $this->normalizeTelegramUsername($this->input('customer_telegram_username')),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'regex:/^\+7\d{10}$/'],
            'customer_telegram_username' => ['required', 'regex:/^@[A-Za-z0-9_]{5,32}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:2000'],
            'customer_comment' => ['nullable', 'string', 'max:2000'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:10240'],
            'items' => ['nullable', 'array', 'min:1', 'required_without:order_groups'],
            'items.*.type' => ['required', Rule::in(['product', 'meal_set'])],
            'items.*.id' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'order_groups' => ['nullable', 'array', 'min:1', 'required_without:items'],
            'order_groups.*.delivery_address' => ['required', 'string', 'max:2000'],
            'order_groups.*.customer_comment' => ['nullable', 'string', 'max:2000'],
            'order_groups.*.items' => ['required', 'array', 'min:1'],
            'order_groups.*.items.*.type' => ['required', Rule::in(['product', 'meal_set'])],
            'order_groups.*.items.*.id' => ['required', 'integer', 'min:1'],
            'order_groups.*.items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Укажите имя получателя.',
            'customer_phone.required' => 'Укажите телефон для связи.',
            'customer_phone.regex' => 'Укажите телефон в российском формате, например +7 (999) 123-45-67.',
            'customer_telegram_username.required' => 'Укажите Telegram-ник для связи по заказу.',
            'customer_telegram_username.regex' => 'Укажите Telegram-ник в формате @username.',
            'customer_email.email' => 'Укажите корректный email или оставьте поле пустым.',
            'delivery_address.required' => 'Укажите адрес доставки.',
            'receipt.required' => 'Прикрепите чек перевода.',
            'receipt.file' => 'Чек должен быть файлом.',
            'receipt.mimes' => 'Чек должен быть в формате JPG, PNG, WEBP или PDF.',
            'receipt.max' => 'Файл чека должен быть не больше 10 МБ.',
            'items.required' => 'Добавьте хотя бы одну позицию в корзину.',
            'items.min' => 'Добавьте хотя бы одну позицию в корзину.',
            'order_groups.required' => 'Добавьте хотя бы одну группу доставки.',
            'order_groups.min' => 'Добавьте хотя бы одну группу доставки.',
            'order_groups.*.delivery_address.required' => 'Укажите адрес для каждой группы доставки.',
            'order_groups.*.items.required' => 'Добавьте хотя бы одну позицию в каждую группу доставки.',
            'order_groups.*.items.min' => 'Добавьте хотя бы одну позицию в каждую группу доставки.',
        ];
    }

    protected function normalizeRussianPhone(mixed $phone): mixed
    {
        if (! is_string($phone)) {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7'.substr($digits, 1);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '7')) {
            return '+'.$digits;
        }

        return $phone;
    }

    protected function normalizeTelegramUsername(mixed $telegramUsername): mixed
    {
        if (! is_string($telegramUsername)) {
            return $telegramUsername;
        }

        $normalized = preg_replace('/\s+/', '', trim($telegramUsername));

        if ($normalized === '') {
            return $normalized;
        }

        if (! str_starts_with($normalized, '@')) {
            $normalized = '@'.$normalized;
        }

        return $normalized;
    }
}
