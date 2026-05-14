<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_amount' => ['nullable', 'numeric', 'min:0'],
            'customize_work' => ['nullable', 'array'],
            'customize_work.*.name' => ['required_with:customize_work', 'string', 'max:200'],
            'customize_work.*.price' => ['required_with:customize_work', 'numeric', 'min:0'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
