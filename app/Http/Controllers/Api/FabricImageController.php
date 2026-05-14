<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FabricImageController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
        ]);

        $path = $this->orderService->uploadFabricImage($request->file('image'));

        return response()->json([
            'path' => $path,
            'url' => asset('storage/'.$path),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $this->orderService->deleteFabricImage($request->path);

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }
}
