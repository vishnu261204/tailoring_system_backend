<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Measurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('orders')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = DB::transaction(function () use ($request) {
            $customer = Customer::create($request->validated());

            if ($request->filled('measurements')) {
                foreach ($request->measurements as $item) {
                    $customer->measurements()->create([
                        'type' => $item['type'],
                        'label' => $item['label'] ?? $item['type'],
                        'data' => $item['fields'] ?? [],
                        'version' => 1,
                    ]);
                }
            }

            return $customer->load('measurements');
        });

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load('measurements');
        $customer->loadCount('orders');

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'gender' => $customer->gender,
                'orders_count' => $customer->orders_count,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ],
            'measurements' => $customer->measurements->map(fn ($m) => [
                'id' => $m->id,
                'customer_id' => $m->customer_id,
                'type' => $m->type,
                'label' => $m->label ?? $m->type,
                'fields' => $m->data,
                'version' => $m->version,
                'created_at' => $m->created_at,
                'updated_at' => $m->updated_at,
            ]),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer = DB::transaction(function () use ($request, $customer) {
            $customer->update($request->validated());

            if ($request->has('deleted_ids')) {
                Measurement::whereIn('id', $request->deleted_ids)
                    ->where('customer_id', $customer->id)
                    ->delete();
            }

            if ($request->has('measurements')) {
                foreach ($request->measurements as $item) {
                    if (isset($item['id'])) {
                        $measurement = Measurement::where('id', $item['id'])
                            ->where('customer_id', $customer->id)
                            ->first();
                        if ($measurement) {
                            $measurement->update([
                                'type' => $item['type'],
                                'label' => $item['label'] ?? $item['type'],
                                'data' => $item['fields'] ?? [],
                            ]);
                        }
                    } else {
                        $latestVersion = Measurement::where('customer_id', $customer->id)
                            ->where('type', $item['type'])
                            ->max('version');

                        $customer->measurements()->create([
                            'type' => $item['type'],
                            'label' => $item['label'] ?? $item['type'],
                            'data' => $item['fields'] ?? [],
                            'version' => ($latestVersion ?? 0) + 1,
                        ]);
                    }
                }
            }

            return $customer->fresh()->load('measurements');
        });

        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        DB::transaction(function () use ($customer) {
            $customer->measurements()->delete();
            $customer->orders()->each(function ($order) {
                $order->items()->delete();
                $order->statusHistory()->delete();
                $order->messageLogs()->delete();
                $order->delete();
            });
            $customer->delete();
        });

        return response()->json([
            'message' => 'Customer deleted successfully',
        ]);
    }
}
