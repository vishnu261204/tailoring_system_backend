<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'order_id' => $this->order->order_id,
                    'customer' => $this->order->customer,
                ];
            }),
            'base_amount' => (float) $this->base_amount,
            'customize_work' => $this->customize_work ?? [],
            'customize_work_total' => collect($this->customize_work)->sum('price'),
            'total_amount' => (float) $this->total_amount,
            'advance_amount' => (float) $this->advance_amount,
            'balance_amount' => (float) $this->balance_amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
