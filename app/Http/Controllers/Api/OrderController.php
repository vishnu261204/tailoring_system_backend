<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()->with(['customer', 'items']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $statuses = explode(',', $request->status);
            $normalizedStatuses = array_map(fn ($s) => $this->normalizeStatus($s), $statuses);
            $normalizedStatuses = array_filter($normalizedStatuses);
            if (! empty($normalizedStatuses)) {
                $query->whereIn('status', $normalizedStatuses);
            }
        }

        $fromDate = $request->query('from_date') ?: $request->query('date_from');
        $toDate = $request->query('to_date') ?: $request->query('date_to');

        if ($fromDate && $toDate) {
            $query->whereBetween('order_date', [$fromDate, $toDate]);
        } else {
            if ($fromDate) {
                $query->whereDate('order_date', '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate('order_date', '<=', $toDate);
            }
        }

        if ($request->filled('billing_status')) {
            $billingStatus = $request->billing_status;

            if ($billingStatus === 'not_billed') {
                $query->whereDoesntHave('billing');
            } elseif ($billingStatus === 'partially_paid') {
                $query->whereHas('billing', fn ($q) => $q->whereColumn('advance_amount', '<', 'base_amount'));
            } elseif ($billingStatus === 'fully_paid') {
                $query->whereHas('billing', fn ($q) => $q->whereColumn('advance_amount', '>=', 'base_amount'));
            }
        }

        $orders = $query->latest()->paginate($request->per_page ?? 15);

        return OrderResource::collection($orders);
    }

    private function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalizedStatus = str_replace(' ', '_', strtolower(trim($status)));

        if ($normalizedStatus === '' || $normalizedStatus === 'all') {
            return null;
        }

        return $normalizedStatus;
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrderAndNotify($request->validated());

        return response()->json([
            'message' => 'Order created successfully',
            'order' => new OrderResource($order),
        ], 201);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'items', 'statusHistory']);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->updateOrder($order, $request->validated());

        return response()->json([
            'message' => 'Order updated successfully',
            'order' => new OrderResource($order),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->updateStatus($order, $request->status);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => new OrderResource($order),
        ]);
    }

    public function destroy(Order $order): JsonResponse
    {
        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->statusHistory()->delete();
            $order->messageLogs()->delete();
            $order->delete();
        });

        return response()->json([
            'message' => 'Order deleted successfully',
        ]);
    }
}
