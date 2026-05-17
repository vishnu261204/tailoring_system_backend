<?php
// app/Models/MessageTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_code',
        'template_type',
        'language',
        'subject',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    // Constants
    const TYPES = [
        'order_created' => 'Order Created',
        'trial_reminder' => 'Trial Reminder',
        'order_completed' => 'Order Completed',
        'delivery_thanks' => 'Delivery Thanks'
    ];

    const LANGUAGES = [
        'en' => 'English',
        'ta' => 'Tamil'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    // Helper Methods
    public function replaceVariables($data)
    {
        $message = $this->body;
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        return $message;
    }

    public function getTypeLabelAttribute()
    {
        return self::TYPES[$this->template_type] ?? ucfirst($this->template_type);
    }

    public function getLanguageLabelAttribute()
    {
        return self::LANGUAGES[$this->language] ?? $this->language;
    }
}