<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Tailoring Management System API is working!',
            'version' => '1.0.0',
            'endpoints' => [
                'auth' => '/api/login, /api/register',
                'customers' => '/api/customers',
                'orders' => '/api/orders',
                'inventory' => '/api/inventory'
            ],
            'status' => 'active',
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
