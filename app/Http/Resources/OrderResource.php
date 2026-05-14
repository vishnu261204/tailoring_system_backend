<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $total = $this->items->sum('price') ?? 0;
        $advance = $this->advance_amount ?? 0;

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'order_date' => $this->order_date,
            'trial_date' => $this->trial_date,
            'delivery_date' => $this->delivery_date,
            'status' => $this->status,
            'advance_amount' => (float) $this->advance_amount,
            'total_amount' => (float) ($this->total_amount ?? $total),
            'balance' => round(max(0, (float) ($this->total_amount ?? $total) - (float) $advance), 2),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => StatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
