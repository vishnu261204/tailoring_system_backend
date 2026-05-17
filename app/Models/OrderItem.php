<?php
// app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'item_type',
        'quantity',
        'measurement_id',
        'measurements_snapshot',
        'stitch_type',
        'design_notes',
        'fabric_id',
        'fabric_name',
        'fabric_quantity_consumed',
        'price_per_item',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'measurements_snapshot' => 'array',
        'fabric_quantity_consumed' => 'decimal:2',
        'price_per_item' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }

    public function fabric()
    {
        return $this->belongsTo(Inventory::class, 'fabric_id');
    }

    // Scopes
    public function scopeShirts($query)
    {
        return $query->where('item_type', 'shirt');
    }

    public function scopePants($query)
    {
        return $query->where('item_type', 'pant');
    }

    public function scopeBlouses($query)
    {
        return $query->where('item_type', 'blouse');
    }

    // Helper Methods
    public function getItemTypeLabelAttribute()
    {
        return ucfirst($this->item_type);
    }

    public function getFormattedPriceAttribute()
    {
        return '₹' . number_format($this->price_per_item, 2);
    }

    public function getFormattedSubtotalAttribute()
    {
        return '₹' . number_format($this->subtotal, 2);
    }

    // Boot Method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->price_per_item * $item->quantity;
        });

        static::created(function ($item) {
            // Deduct fabric from inventory if fabric is used
            if ($item->fabric_id && $item->fabric_quantity_consumed) {
                $fabric = Inventory::find($item->fabric_id);
                if ($fabric) {
                    $fabric->deductStock($item->fabric_quantity_consumed);
                }
            }
            
            // Update order totals
            $item->order->calculateTotals();
        });
    }
}