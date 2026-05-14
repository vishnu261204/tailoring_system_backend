<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Resources\MeasurementResource;
use App\Models\Customer;
use App\Models\Measurement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeasurementController extends Controller
{
    public function index(Customer $customer, Request $request): AnonymousResourceCollection
    {
        $query = $customer->measurements();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $measurements = $query->latest('version')->paginate($request->per_page ?? 15);

        return MeasurementResource::collection($measurements);
    }

    public function latest(Customer $customer, Request $request): JsonResponse
    {
        $query = $customer->measurements();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $measurement = $query->latest('version')->first();

        if (! $measurement) {
            return response()->json([
                'message' => 'No measurements found for this customer',
            ], 404);
        }

        return response()->json([
            'measurement' => new MeasurementResource($measurement),
        ]);
    }

    public function store(StoreMeasurementRequest $request, Customer $customer): JsonResponse
    {
        $latestVersion = Measurement::where('customer_id', $customer->id)
            ->where('type', $request->type)
            ->max('version');

        $measurement = $customer->measurements()->create([
            'type' => $request->type,
            'data' => $request->data,
            'version' => ($latestVersion ?? 0) + 1,
        ]);

        return response()->json([
            'message' => 'Measurement added successfully',
            'measurement' => new MeasurementResource($measurement),
        ], 201);
    }
}
