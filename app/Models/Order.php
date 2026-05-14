<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'order_date',
        'trial_date',
        'delivery_date',
        'status',
        'advance_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'trial_date' => 'date',
            'delivery_date' => 'date',
            'advance_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function getBalanceAttribute(): float
    {
        return round(max(0, ($this->total_amount ?? 0) - ($this->advance_amount ?? 0)), 2);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function billing(): HasOne
    {
        return $this->hasOne(Billing::class);
    }
}
