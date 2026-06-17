<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'saved_delivery_addresses' => $this->normalizePresets($this->input('saved_delivery_addresses')),
            'saved_delivery_comments' => $this->normalizePresets($this->input('saved_delivery_comments')),
        ]);
    }

    public function rules(): array
    {
        return [
            'saved_delivery_addresses' => ['nullable', 'array', 'max:20'],
            'saved_delivery_addresses.*.id' => ['required', 'string', 'max:100'],
            'saved_delivery_addresses.*.label' => ['nullable', 'string', 'max:120'],
            'saved_delivery_addresses.*.value' => ['required', 'string', 'max:2000'],
            'saved_delivery_comments' => ['nullable', 'array', 'max:20'],
            'saved_delivery_comments.*.id' => ['required', 'string', 'max:100'],
            'saved_delivery_comments.*.label' => ['nullable', 'string', 'max:120'],
            'saved_delivery_comments.*.value' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function normalizePresets(mixed $presets): array
    {
        if (! is_array($presets)) {
            return [];
        }

        return collect($presets)
            ->map(function ($preset): ?array {
                if (! is_array($preset)) {
                    return null;
                }

                $id = isset($preset['id']) && is_string($preset['id']) ? trim($preset['id']) : '';
                $label = isset($preset['label']) && is_string($preset['label']) ? trim($preset['label']) : '';
                $value = isset($preset['value']) && is_string($preset['value']) ? trim($preset['value']) : '';

                if ($id === '' || $value === '') {
                    return null;
                }

                return [
                    'id' => $id,
                    'label' => $label !== '' ? $label : null,
                    'value' => $value,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
