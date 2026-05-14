<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'item_type',
        'custom_name',
        'measurements',
        'notes',
        'fabric_details',
        'quantity',
        'meter',
        'fabric_image',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'measurements' => 'array',
            'meter' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function getFabricImageUrlAttribute(): ?string
    {
        if (! $this->fabric_image) {
            return null;
        }

        return asset('storage/'.$this->fabric_image);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
