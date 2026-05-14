<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:shirt,pant,blouse,kurta,suit,sherwani,coat,jacket,churidar,skirt,lehenga,salwar,other,custom'],
            'data' => ['required', 'array'],
        ];
    }
}
