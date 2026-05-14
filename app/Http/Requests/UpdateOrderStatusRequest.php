<?php

namespace App\Http\Requests;

use App\Services\OrderService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', $this->validStatuses())],
        ];
    }

    private function validStatuses(): array
    {
        return [
            OrderService::STATUS_PENDING,
            OrderService::STATUS_IN_PROGRESS,
            OrderService::STATUS_TRIAL_READY,
            OrderService::STATUS_COMPLETED,
            OrderService::STATUS_DELIVERED,
            OrderService::STATUS_CANCELLED,
        ];
    }
}
