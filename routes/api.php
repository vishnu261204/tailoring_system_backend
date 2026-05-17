<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\MeasurementController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\WhatsAppController;
use App\Http\Controllers\API\ReportController;

Route::get('/', function() {
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
});

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    
    // Users management (Admin only)
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::put('/users/{id}/status', [AuthController::class, 'updateUserStatus']);
    
    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::get('/customers/{id}/orders', [CustomerController::class, 'orders']);
    Route::get('/customers/{id}/measurements', [CustomerController::class, 'measurements']);
    
    // ============= ORDERS =============
    Route::apiResource('orders', OrderController::class);
    Route::get('/orders/statistics', [OrderController::class, 'statistics']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::get('/orders/{id}/invoice', [OrderController::class, 'generateInvoice']);
    Route::post('/orders/{id}/send-whatsapp', [OrderController::class, 'sendWhatsApp']);
    Route::get('/orders/{id}/download-invoice', [OrderController::class, 'downloadInvoice']);
    
    // ============= MEASUREMENTS =============
    Route::apiResource('measurements', MeasurementController::class);
    Route::get('/customers/{customerId}/measurements/latest', [MeasurementController::class, 'latest']);
    Route::get('/customers/{customerId}/measurements/history', [MeasurementController::class, 'history']);
    

    // ============= DASHBOARD =============
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/charts', [DashboardController::class, 'charts']);
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders']);
    Route::get('/dashboard/upcoming-deliveries', [DashboardController::class, 'upcomingDeliveries']);
    
    // ============= WHATSAPP =============
    Route::get('/whatsapp/templates', [WhatsAppController::class, 'templates']);
    Route::post('/whatsapp/send', [WhatsAppController::class, 'send']);
    Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);
    Route::get('/whatsapp/logs', [WhatsAppController::class, 'logs']);
    
    // ============= REPORTS =============
    // Route::get('/reports/orders', [ReportController::class, 'orders']);
    // Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    // Route::get('/reports/inventory', [ReportController::class, 'inventory']);
    // Route::get('/reports/customers', [ReportController::class, 'customers']);
    
    // // ============= SETTINGS =============
    // Route::get('/settings', [App\Http\Controllers\API\SettingController::class, 'index']);
    // Route::put('/settings', [App\Http\Controllers\API\SettingController::class, 'update']);
    // Route::get('/settings/{group}', [App\Http\Controllers\API\SettingController::class, 'getByGroup']);
});