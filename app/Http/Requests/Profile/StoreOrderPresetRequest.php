<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('value');
        $label = $this->input('label');

        $this->merge([
            'value' => is_string($value) ? trim($value) : $value,
            'label' => is_string($label) ? trim($label) : $label,
        ]);
    }

    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(['delivery_address', 'delivery_comment'])],
            'value' => ['required', 'string', 'max:2000'],
            'label' => ['nullable', 'string', 'max:120'],
        ];
    }
}
