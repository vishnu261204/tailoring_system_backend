<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillingRequest;
use App\Http\Requests\UpdateBillingRequest;
use App\Http\Resources\BillingResource;
use App\Models\Billing;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    public function index(): JsonResponse
    {
        $billings = Billing::with('order.customer')
            ->latest()
            ->get();

        return response()->json([
            'billings' => BillingResource::collection($billings),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        $billing = Billing::where('order_id', $order->id)
            ->with('order.customer')
            ->first();

        if (! $billing) {
            $order->load('customer', 'items');

            $baseAmount = $order->items->sum('price') ?? 0;

            return response()->json([
                'billing' => null,
                'order' => [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'customer' => $order->customer,
                    'base_amount' => $baseAmount,
                    'items' => $order->items,
                    'advance_amount' => $order->advance_amount ?? 0,
                ],
            ]);
        }

        return response()->json([
            'billing' => new BillingResource($billing),
        ]);
    }

    public function store(StoreBillingRequest $request): JsonResponse
    {
        $order = Order::findOrFail($request->order_id);

        if (Billing::where('order_id', $order->id)->exists()) {
            return response()->json([
                'message' => 'Billing already exists for this order',
            ], 422);
        }

        $totals = Billing::calculateTotals($request->validated());

        $billing = Billing::create([
            'order_id' => $request->order_id,
            'base_amount' => $request->base_amount ?? 0,
            'customize_work' => $request->customize_work ?? [],
            'total_amount' => $totals['total_amount'],
            'advance_amount' => $request->advance_amount ?? 0,
            'balance_amount' => $totals['balance_amount'],
            'notes' => $request->notes,
        ]);

        $billing->load('order.customer');

        return response()->json([
            'message' => 'Billing generated successfully',
            'billing' => new BillingResource($billing),
        ], 201);
    }

    public function update(UpdateBillingRequest $request, Order $order): JsonResponse
    {
        $billing = Billing::where('order_id', $order->id)->first();

        if (! $billing) {
            return response()->json([
                'message' => 'Billing not found for this order',
            ], 404);
        }

        $totals = Billing::calculateTotals($request->validated());

        $billing->update([
            'base_amount' => $request->base_amount ?? 0,
            'customize_work' => $request->customize_work ?? [],
            'total_amount' => $totals['total_amount'],
            'advance_amount' => $request->advance_amount ?? 0,
            'balance_amount' => $totals['balance_amount'],
            'notes' => $request->notes,
        ]);

        $billing->load('order.customer');

        return response()->json([
            'message' => 'Billing updated successfully',
            'billing' => new BillingResource($billing->fresh()),
        ]);
    }
}
