<?php
// app/Models/MessageLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'phone_number',
        'template_type',
        'message_content',
        'status',
        'whatsapp_message_id',
        'error_message',
        'retry_count',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    // Constants
    const STATUSES = [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'failed' => 'Failed',
        'read' => 'Read'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone_number', $phone);
    }

    // Helper Methods
    public function markAsSent($messageId = null)
    {
        $this->status = 'sent';
        $this->whatsapp_message_id = $messageId;
        $this->sent_at = now();
        $this->save();
    }

    public function markAsDelivered()
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();
    }

    public function markAsRead()
    {
        $this->status = 'read';
        $this->read_at = now();
        $this->save();
    }

    public function markAsFailed($error)
    {
        $this->status = 'failed';
        $this->error_message = $error;
        $this->retry_count++;
        $this->save();
    }

    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}