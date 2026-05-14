<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'measurements' => ['nullable', 'array'],
            'measurements.*.type' => ['required_with:measurements', 'string', 'max:50'],
            'measurements.*.label' => ['nullable', 'string', 'max:100'],
            'measurements.*.fields' => ['nullable', 'array'],
        ];
    }
}
