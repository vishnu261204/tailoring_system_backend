<?php
// app/Models/Inventory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory';

    protected $fillable = [
        'fabric_code',
        'fabric_name',
        'fabric_type',
        'color',
        'quantity',
        'unit',
        'minimum_stock',
        'cost_per_meter',
        'selling_price_per_meter',
        'supplier',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'cost_per_meter' => 'decimal:2',
        'selling_price_per_meter' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'fabric_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity <= minimum_stock');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('fabric_type', $type);
    }

    // Helper Methods
    public function isLowStock()
    {
        return $this->quantity <= $this->minimum_stock;
    }

    public function deductStock($quantity)
    {
        if ($this->quantity >= $quantity) {
            $this->quantity -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    public function addStock($quantity)
    {
        $this->quantity += $quantity;
        $this->save();
    }

    public function getStockStatusAttribute()
    {
        if ($this->quantity <= 0) return 'Out of Stock';
        if ($this->quantity <= $this->minimum_stock) return 'Low Stock';
        return 'In Stock';
    }

    public function getStockStatusColorAttribute()
    {
        if ($this->quantity <= 0) return 'red';
        if ($this->quantity <= $this->minimum_stock) return 'orange';
        return 'green';
    }
}