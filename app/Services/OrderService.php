<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderService
{
    const STATUS_PENDING = 'pending';

    const STATUS_IN_PROGRESS = 'in_progress';

    const STATUS_TRIAL_READY = 'trial_ready';

    const STATUS_COMPLETED = 'completed';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_CANCELLED = 'cancelled';

    const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_TRIAL_READY, self::STATUS_CANCELLED],
        self::STATUS_TRIAL_READY => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'order_id' => $this->generateOrderId(),
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'] ?? today(),
                'trial_date' => $data['trial_date'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'status' => self::STATUS_PENDING,
                'advance_amount' => $data['advance_amount'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $measurements = $item['measurements'] ?? [];

                $order->items()->create([
                    'item_type' => $item['item_type'],
                    'custom_name' => $item['custom_name'] ?? null,
                    'measurements' => $measurements,
                    'notes' => $item['notes'] ?? null,
                    'fabric_details' => $item['fabric_details'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'meter' => $item['meter'] ?? null,
                    'fabric_image' => $item['fabric_image'] ?? null,
                    'price' => $item['price'] ?? null,
                ]);
            }

            $totalAmount = round($order->items()->sum('price') ?? 0, 2);
            $order->update(['total_amount' => $totalAmount]);

            $order->statusHistory()->create([
                'old_status' => null,
                'new_status' => self::STATUS_PENDING,
                'changed_at' => now(),
            ]);

            return $order->fresh()->load('items', 'customer', 'statusHistory');
        });
    }

    public function createOrderAndNotify(array $data): Order
    {
        return $this->createOrder($data);
    }

    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'order_date' => isset($data['order_date']) ? $data['order_date'] : $order->order_date,
                'trial_date' => $data['trial_date'] ?? $order->trial_date,
                'delivery_date' => $data['delivery_date'] ?? $order->delivery_date,
                'advance_amount' => $data['advance_amount'] ?? $order->advance_amount,
            ]);

            if (isset($data['items'])) {
                $order->items()->delete();

                foreach ($data['items'] as $item) {
                    $measurements = $item['measurements'] ?? [];

                    $order->items()->create([
                        'item_type' => $item['item_type'],
                        'custom_name' => $item['custom_name'] ?? null,
                        'measurements' => $measurements,
                        'notes' => $item['notes'] ?? null,
                        'fabric_details' => $item['fabric_details'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'meter' => $item['meter'] ?? null,
                        'fabric_image' => $item['fabric_image'] ?? null,
                        'price' => $item['price'] ?? null,
                    ]);
                }

                $totalAmount = round($order->items()->sum('price') ?? 0, 2);
                $order->update(['total_amount' => $totalAmount]);
            }

            return $order->fresh()->load('items', 'customer');
        });
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        $oldStatus = $order->status;

        if (! $this->isValidTransition($oldStatus, $newStatus)) {
            abort(422, "Status transition from '{$oldStatus}' to '{$newStatus}' is not allowed.");
        }

        $order = DB::transaction(function () use ($order, $oldStatus, $newStatus) {
            $order->update(['status' => $newStatus]);

            $order->statusHistory()->create([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_at' => now(),
            ]);

            return $order->fresh()->load('statusHistory');
        });

        return $order;
    }

    public function isValidTransition(?string $oldStatus, string $newStatus): bool
    {
        if ($oldStatus === null) {
            return $newStatus === self::STATUS_PENDING;
        }

        return in_array($newStatus, self::TRANSITIONS[$oldStatus] ?? []);
    }

    public function generateOrderId(): string
    {
        $year = now()->year;
        $prefix = "ORD-{$year}-";

        $lastOrder = Order::where('order_id', 'like', "{$prefix}%")
            ->orderBy('order_id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function uploadFabricImage($file): ?string
    {
        if (! $file) {
            return null;
        }

        $path = $file->store('fabric-images', 'public');

        return $path;
    }

    public function deleteFabricImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
