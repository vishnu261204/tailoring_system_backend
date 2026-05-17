<?php
// app/Models/Measurement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    use HasFactory;

    protected $table = 'measurements';

    protected $fillable = [
        'customer_id',
        'version',
        'is_current',
        'shirt_chest',
        'shirt_waist',
        'shirt_shoulder',
        'shirt_sleeve',
        'shirt_collar',
        'shirt_length',
        'pant_waist',
        'pant_hip',
        'pant_length',
        'pant_inseam',
        'pant_thigh',
        'pant_bottom',
        'blouse_chest',
        'blouse_waist',
        'blouse_shoulder',
        'blouse_sleeve',
        'blouse_length',
        'custom_measurements',
        'notes',
        'measured_by',
        'measurement_date',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'custom_measurements' => 'array',
        'shirt_chest' => 'decimal:2',
        'shirt_waist' => 'decimal:2',
        'shirt_shoulder' => 'decimal:2',
        'shirt_sleeve' => 'decimal:2',
        'shirt_collar' => 'decimal:2',
        'shirt_length' => 'decimal:2',
        'pant_waist' => 'decimal:2',
        'pant_hip' => 'decimal:2',
        'pant_length' => 'decimal:2',
        'pant_inseam' => 'decimal:2',
        'pant_thigh' => 'decimal:2',
        'pant_bottom' => 'decimal:2',
        'blouse_chest' => 'decimal:2',
        'blouse_waist' => 'decimal:2',
        'blouse_shoulder' => 'decimal:2',
        'blouse_sleeve' => 'decimal:2',
        'blouse_length' => 'decimal:2',
        'measurement_date' => 'date',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function measuredBy()
    {
        return $this->belongsTo(User::class, 'measured_by');
    }

    public function orders()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeForGarment($query, $garmentType)
    {
        switch ($garmentType) {
            case 'shirt':
                return $query->whereNotNull('shirt_chest');
            case 'pant':
                return $query->whereNotNull('pant_waist');
            case 'blouse':
                return $query->whereNotNull('blouse_chest');
            default:
                return $query;
        }
    }

    // Helper Methods
    public function getShirtMeasurements()
    {
        return [
            'chest' => $this->shirt_chest,
            'waist' => $this->shirt_waist,
            'shoulder' => $this->shirt_shoulder,
            'sleeve' => $this->shirt_sleeve,
            'collar' => $this->shirt_collar,
            'length' => $this->shirt_length,
        ];
    }

    public function getPantMeasurements()
    {
        return [
            'waist' => $this->pant_waist,
            'hip' => $this->pant_hip,
            'length' => $this->pant_length,
            'inseam' => $this->pant_inseam,
            'thigh' => $this->pant_thigh,
            'bottom' => $this->pant_bottom,
        ];
    }

    public function getBlouseMeasurements()
    {
        return [
            'chest' => $this->blouse_chest,
            'waist' => $this->blouse_waist,
            'shoulder' => $this->blouse_shoulder,
            'sleeve' => $this->blouse_sleeve,
            'length' => $this->blouse_length,
        ];
    }

    // Boot Method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($measurement) {
            // Set version number
            $lastVersion = static::where('customer_id', $measurement->customer_id)
                                ->max('version') ?? 0;
            $measurement->version = $lastVersion + 1;

            // Set previous measurements as not current
            if ($measurement->is_current) {
                static::where('customer_id', $measurement->customer_id)
                      ->update(['is_current' => false]);
            }
        });
    }
}