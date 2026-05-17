<?php
// app/Models/Customer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'gender',
        'total_orders',
        'total_spent',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_orders' => 'integer',
        'total_spent' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function measurements()
    {
        return $this->hasMany(Measurement::class);
    }

    public function messageLogs()
    {
        return $this->hasMany(MessageLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
                     ->orWhere('phone', 'LIKE', "%{$search}%")
                     ->orWhere('customer_code', 'LIKE', "%{$search}%");
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        return $this->address ?? 'No address provided';
    }

    // Helper Methods
    public function updateTotals()
    {
        $this->total_orders = $this->orders()->count();
        $this->total_spent = $this->orders()->sum('total_amount');
        $this->save();
    }

    public function getLatestMeasurements()
    {
        return $this->measurements()
                    ->where('is_current', true)
                    ->latest()
                    ->first();
    }
}