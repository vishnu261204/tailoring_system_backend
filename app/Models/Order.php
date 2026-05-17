<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'user_id',
        'order_date',
        'trial_date',
        'delivery_date',
        'status',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'advance_paid',
        'balance_amount',
        'payment_status',
        'notes',
        'whatsapp_notification_sent',
    ];

    protected $casts = [
        'order_date' => 'date',
        'trial_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'advance_paid' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'whatsapp_notification_sent' => 'boolean',
    ];

    // Constants
    const STATUSES = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'trial' => 'Trial',
        'completed' => 'Completed',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];

    const PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'partial' => 'Partial',
        'paid' => 'Paid'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function messageLogs()
    {
        return $this->hasMany(MessageLog::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeTodayDelivery($query)
    {
        return $query->whereDate('delivery_date', today());
    }

    public function scopeTodayTrial($query)
    {
        return $query->whereDate('trial_date', today());
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    // Helper Methods
    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->total_amount = $this->subtotal - $this->discount + $this->tax;
        $this->balance_amount = $this->total_amount - $this->advance_paid;
        
        // Update payment status
        if ($this->balance_amount <= 0) {
            $this->payment_status = 'paid';
        } elseif ($this->advance_paid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }
        
        $this->save();
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getPaymentStatusLabelAttribute()
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? ucfirst($this->payment_status);
    }

    public function canTransitionTo($newStatus)
    {
        $transitions = [
            'pending' => ['in_progress', 'cancelled'],
            'in_progress' => ['trial', 'cancelled'],
            'trial' => ['completed', 'in_progress', 'cancelled'],
            'completed' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => [],
        ];

        return in_array($newStatus, $transitions[$this->status] ?? []);
    }

    // Boot Method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($order) {
            $order->customer->updateTotals();
        });
    }
}