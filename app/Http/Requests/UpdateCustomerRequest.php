<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = $customer instanceof Customer ? $customer->id : $customer;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:customers,phone,'.$customerId],
            'address' => ['nullable', 'string', 'max:500'],
            'gender' => ['sometimes', 'string', 'in:male,female,other'],
            'measurements' => ['nullable', 'array'],
            'measurements.*.id' => ['nullable', 'integer', 'exists:measurements,id'],
            'measurements.*.type' => ['required_with:measurements', 'string', 'max:50'],
            'measurements.*.label' => ['nullable', 'string', 'max:100'],
            'measurements.*.fields' => ['nullable', 'array'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:measurements,id'],
        ];
    }
}
