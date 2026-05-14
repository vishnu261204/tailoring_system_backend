<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FabricImageController;
use App\Http\Controllers\Api\MeasurementController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware('role:admin')->group(function () {
        // Admin-only routes go here
    });

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('dashboard', DashboardController::class);

        Route::apiResource('customers', CustomerController::class);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::put('orders/{order}', [OrderController::class, 'update']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::delete('orders/{order}', [OrderController::class, 'destroy']);

        Route::post('fabric-image/upload', [FabricImageController::class, 'upload']);
        Route::delete('fabric-image', [FabricImageController::class, 'delete']);

        Route::get('customers/{customer}/measurements', [MeasurementController::class, 'index']);
        Route::post('customers/{customer}/measurements', [MeasurementController::class, 'store']);
        Route::get('customers/{customer}/measurements/latest', [MeasurementController::class, 'latest']);
        Route::put('customers/{customer}/measurements/{measurement}', [MeasurementController::class, 'update']);
        Route::delete('customers/{customer}/measurements', [MeasurementController::class, 'destroy']);

        Route::get('billings', [BillingController::class, 'index']);
        Route::get('billings/{order}', [BillingController::class, 'show']);
        Route::post('billings', [BillingController::class, 'store']);
        Route::put('billings/{order}', [BillingController::class, 'update']);
    });
});
