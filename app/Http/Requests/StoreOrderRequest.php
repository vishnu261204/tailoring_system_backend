<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    const ITEM_TYPES = 'shirt,pant,blouse,kurta,suit,sherwani,coat,jacket,churidar,skirt,lehenga,salwar,other,custom';

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'order_date' => ['nullable', 'date'],
            'trial_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:trial_date'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', 'in:'.self::ITEM_TYPES],
            'items.*.custom_name' => ['nullable', 'string', 'max:100', 'required_if:items.*.item_type,custom'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'items.*.fabric_details' => ['nullable', 'string', 'max:1000'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.meter' => ['nullable', 'numeric', 'min:0'],
            'items.*.fabric_image' => ['nullable', 'string'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.measurements' => ['nullable', 'array'],
            'items.*.measurements.*.key' => ['required_with:items.*.measurements', 'string', 'max:100'],
            'items.*.measurements.*.label' => ['nullable', 'string', 'max:100'],
            'items.*.measurements.*.labelTa' => ['nullable', 'string', 'max:100'],
            'items.*.measurements.*.value' => ['nullable'],
            'items.*.measurements.*.isCustom' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.measurements.*.key.required_with' => 'Measurement key is required',
        ];
    }
}
