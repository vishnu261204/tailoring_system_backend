<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\MeasurementController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "web" middleware group.
|
*/

// ============= PUBLIC ROUTES (No Authentication Required) =============

// Home/Welcome page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Login page
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Register page
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// Forgot password
Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

// API test endpoint (for debugging)
Route::get('/api-test', function() {
    return response()->json([
        'message' => 'Web routes are working!',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// ============= PROTECTED ROUTES (Require Authentication) =============
Route::middleware(['auth'])->group(function () {
    
    // Dashboard (Home after login)
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // User Profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');
    
    // ============= CUSTOMER MANAGEMENT =============
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/create', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/orders', [CustomerController::class, 'orders'])->name('orders');
        Route::get('/{id}/measurements', [CustomerController::class, 'measurements'])->name('measurements');
        Route::post('/{id}/measurements', [CustomerController::class, 'addMeasurement'])->name('add-measurement');
    });
    
    // ============= ORDER MANAGEMENT =============
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{id}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{id}', [OrderController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->name('status');
        Route::get('/{id}/invoice', [OrderController::class, 'generateInvoice'])->name('invoice');
        Route::post('/{id}/send-whatsapp', [OrderController::class, 'sendWhatsApp'])->name('send-whatsapp');
        Route::get('/statistics', [OrderController::class, 'statistics'])->name('statistics');
    });
    
    // ============= MEASUREMENT MANAGEMENT =============
    Route::prefix('measurements')->name('measurements.')->group(function () {
        Route::get('/customer/{customerId}', [MeasurementController::class, 'index'])->name('index');
        Route::get('/create/{customerId}', [MeasurementController::class, 'create'])->name('create');
        Route::post('/', [MeasurementController::class, 'store'])->name('store');
        Route::get('/{id}', [MeasurementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [MeasurementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MeasurementController::class, 'update'])->name('update');
        Route::delete('/{id}', [MeasurementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/set-current', [MeasurementController::class, 'setCurrent'])->name('set-current');
    });
    
    // ============= INVENTORY MANAGEMENT =============
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{id}', [InventoryController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [InventoryController::class, 'destroy'])->name('destroy');
        Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock');
        Route::post('/{id}/deduct', [InventoryController::class, 'deductStock'])->name('deduct');
        Route::post('/{id}/add', [InventoryController::class, 'addStock'])->name('add');
    });
    
    // ============= REPORTS =============
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/orders', [ReportController::class, 'orders'])->name('orders');
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });
    
    // ============= SETTINGS (Admin only) =============
    Route::prefix('settings')->name('settings.')->middleware(['admin'])->group(function () {
        Route::get('/', [App\Http\Controllers\Web\SettingController::class, 'index'])->name('index');
        Route::put('/', [App\Http\Controllers\Web\SettingController::class, 'update'])->name('update');
        Route::get('/whatsapp-templates', [App\Http\Controllers\Web\SettingController::class, 'whatsappTemplates'])->name('whatsapp-templates');
        Route::put('/whatsapp-templates/{id}', [App\Http\Controllers\Web\SettingController::class, 'updateTemplate'])->name('update-template');
        Route::get('/users', [App\Http\Controllers\Web\SettingController::class, 'users'])->name('users');
        Route::post('/users', [App\Http\Controllers\Web\SettingController::class, 'createUser'])->name('create-user');
        Route::put('/users/{id}', [App\Http\Controllers\Web\SettingController::class, 'updateUser'])->name('update-user');
    });
});

// ============= FALLBACK ROUTE (404 Page) =============
Route::fallback(function () {
    return view('errors.404');
});