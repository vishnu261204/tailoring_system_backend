<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_type' => $this->item_type,
            'custom_name' => $this->custom_name,
            'display_name' => $this->item_type === 'custom' ? ($this->custom_name ?? 'Custom') : ucfirst($this->item_type),
            'measurements' => $this->measurements,
            'notes' => $this->notes,
            'fabric_details' => $this->fabric_details,
            'quantity' => $this->quantity,
            'meter' => $this->meter ? (float) $this->meter : null,
            'fabric_image' => $this->fabric_image,
            'fabric_image_url' => $this->fabric_image ? asset('storage/'.$this->fabric_image) : null,
            'price' => $this->price ? (float) $this->price : null,
        ];
    }
}
