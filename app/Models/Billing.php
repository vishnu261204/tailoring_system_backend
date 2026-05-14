<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Billing extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'base_amount',
        'customize_work',
        'total_amount',
        'advance_amount',
        'balance_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'customize_work' => 'array',
            'base_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function calculateTotals(array $data): array
    {
        $baseAmount = $data['base_amount'] ?? 0;
        $customizeWork = $data['customize_work'] ?? [];
        $advanceAmount = $data['advance_amount'] ?? 0;

        $customizeWorkTotal = collect($customizeWork)->sum('price');
        $totalAmount = round($baseAmount + $customizeWorkTotal, 2);
        $balanceAmount = round($totalAmount - $advanceAmount, 2);

        return [
            'total_amount' => $totalAmount,
            'balance_amount' => max(0, $balanceAmount),
        ];
    }
}
