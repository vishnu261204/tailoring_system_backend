<?php
// app/Models/SystemSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'group_name',
        'description',
    ];

    protected $casts = [
        'setting_value' => 'string',
    ];

    // Scopes
    public function scopeByGroup($query, $group)
    {
        return $query->where('group_name', $group);
    }

    public function scopeGeneral($query)
    {
        return $query->where('group_name', 'general');
    }

    // Helper Methods
    public function getTypedValueAttribute()
    {
        switch ($this->setting_type) {
            case 'boolean':
                return filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($this->setting_value) ? (float) $this->setting_value : 0;
            case 'json':
                return json_decode($this->setting_value, true);
            default:
                return $this->setting_value;
        }
    }

    public function setTypedValueAttribute($value)
    {
        switch ($this->setting_type) {
            case 'boolean':
                $this->setting_value = $value ? 'true' : 'false';
                break;
            case 'json':
                $this->setting_value = json_encode($value);
                break;
            default:
                $this->setting_value = (string) $value;
        }
    }

    // Static Helper
    public static function getValue($key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        if (!$setting) {
            return $default;
        }
        return $setting->typed_value;
    }

    public static function setValue($key, $value, $description = null)
    {
        $setting = static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value, 'description' => $description]
        );
        
        if ($setting->setting_type === 'boolean') {
            $setting->setting_value = $value ? 'true' : 'false';
            $setting->save();
        }
        
        return $setting;
    }

    public static function getGroup($group)
    {
        return static::where('group_name', $group)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->setting_key => $item->typed_value];
                    });
    }
}