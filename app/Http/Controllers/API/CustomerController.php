<?php
// app/Http/Controllers/API/CustomerController.php

namespace App\Http\Controllers\API;

use App\Models\Customer;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    // Get all customers
    public function index(Request $request)
    {
        $query = Customer::query();
        
        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('customer_code', 'LIKE', "%{$search}%");
            });
        }
        
        // Filter by active status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $customers = $query->latest()->paginate($perPage);
        
        return response()->json($customers);
    }
    
    // Get single customer
    public function show($id)
    {
        $customer = Customer::with(['orders' => function($q) {
            $q->latest()->limit(10);
        }, 'measurements' => function($q) {
            $q->where('is_current', true)->latest();
        }])->findOrFail($id);
        
        return response()->json($customer);
    }
    
    // Store customer
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'email' => 'nullable|email|max:100|unique:customers,email',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable|string',
        ]);
        
        // Generate customer code
        $lastCustomer = Customer::orderBy('id', 'desc')->first();
        $lastNumber = $lastCustomer ? intval(substr($lastCustomer->customer_code, 4)) : 0;
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        $customerCode = 'CUST' . $newNumber;
        
        $customer = Customer::create([
            'customer_code' => $customerCode,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'gender' => $request->gender,
            'notes' => $request->notes,
            'is_active' => true,
        ]);
        
        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'create',
            'customer',
            "Created customer: {$customer->name}",
            null,
            $customer->toArray()
        );
        
        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => $customer
        ], 201);
    }
    
    // Update customer
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:20|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|max:100|unique:customers,email,' . $customer->id,
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
        
        $oldData = $customer->toArray();
        $customer->update($request->only([
            'name', 'phone', 'email', 'address', 'gender', 'notes', 'is_active'
        ]));
        
        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'update',
            'customer',
            "Updated customer: {$customer->name}",
            $oldData,
            $customer->toArray()
        );
        
        return response()->json([
            'message' => 'Customer updated successfully',
            'customer' => $customer
        ]);
    }
    
    // Delete customer (soft delete)
    public function destroy(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete customer with existing orders'
            ], 422);
        }
        
        $oldData = $customer->toArray();
        $customer->delete();
        
        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'delete',
            'customer',
            "Deleted customer: {$customer->name}",
            $oldData,
            null
        );
        
        return response()->json([
            'message' => 'Customer deleted successfully'
        ]);
    }
    
    // Get customer orders
    public function orders($id)
    {
        $customer = Customer::findOrFail($id);
        $orders = $customer->orders()->with('user')->latest()->paginate(10);
        
        return response()->json($orders);
    }
    
    // Get customer measurements
    public function measurements($id)
    {
        $customer = Customer::findOrFail($id);
        $measurements = $customer->measurements()->orderBy('version', 'desc')->get();
        
        return response()->json($measurements);
    }
}