<?php

namespace App\Http\Controllers\API;

use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Measurement;
use App\Models\Inventory;
use App\Models\ActivityLog;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Services\whatsAppService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $whatsappService;
    protected $invoiceService;

    public function __construct(WhatsAppService $whatsappService, InvoiceService $invoiceService)
    {
        $this->whatsappService = $whatsappService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Get all orders with filters
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'user', 'items']);

        // Search by order number or customer
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('phone', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Today's deliveries
        if ($request->has('today_delivery')) {
            $query->whereDate('delivery_date', today());
        }

        // Today's trials
        if ($request->has('today_trial')) {
            $query->whereDate('trial_date', today());
        }

        $perPage = $request->get('per_page', 15);
        $orders = $query->latest()->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Get single order details
     */
    public function show($id)
    {
        $order = Order::with(['customer', 'user', 'items.measurement', 'items.fabric'])
                      ->findOrFail($id);
        
        return response()->json($order);
    }

    /**
     * Create new order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'trial_date' => 'nullable|date|after_or_equal:order_date',
            'delivery_date' => 'nullable|date|after_or_equal:trial_date',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:shirt,pant,blouse,other',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_per_item' => 'required|numeric|min:0',
            'items.*.measurement_id' => 'nullable|exists:measurements,id',
            'items.*.stitch_type' => 'nullable|string',
            'items.*.design_notes' => 'nullable|string',
            'items.*.fabric_id' => 'nullable|exists:inventory,id',
            'items.*.fabric_quantity_consumed' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'advance_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['price_per_item'] * $item['quantity'];
            }

            $discount = $request->discount ?? 0;
            $tax = $request->tax ?? 0;
            $totalAmount = $subtotal - $discount + $tax;
            $advancePaid = $request->advance_paid ?? 0;
            $balanceAmount = $totalAmount - $advancePaid;

            // Generate order number
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $lastNumber = $lastOrder ? intval(substr($lastOrder->order_number, 4)) : 0;
            $orderNumber = 'ORD-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

            // Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $request->customer_id,
                'user_id' => $request->user()->id,
                'order_date' => $request->order_date,
                'trial_date' => $request->trial_date,
                'delivery_date' => $request->delivery_date,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $totalAmount,
                'advance_paid' => $advancePaid,
                'balance_amount' => $balanceAmount,
                'payment_status' => $advancePaid >= $totalAmount ? 'paid' : ($advancePaid > 0 ? 'partial' : 'pending'),
                'notes' => $request->notes,
                'whatsapp_notification_sent' => false,
            ]);

            // Create order items
            foreach ($request->items as $item) {
                // Get measurement snapshot if measurement_id provided
                $measurementSnapshot = null;
                if (isset($item['measurement_id'])) {
                    $measurement = Measurement::find($item['measurement_id']);
                    if ($measurement) {
                        $measurementSnapshot = $measurement->toArray();
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_type' => $item['item_type'],
                    'quantity' => $item['quantity'],
                    'measurement_id' => $item['measurement_id'] ?? null,
                    'measurements_snapshot' => $measurementSnapshot,
                    'stitch_type' => $item['stitch_type'] ?? null,
                    'design_notes' => $item['design_notes'] ?? null,
                    'fabric_id' => $item['fabric_id'] ?? null,
                    'fabric_name' => $item['fabric_name'] ?? null,
                    'fabric_quantity_consumed' => $item['fabric_quantity_consumed'] ?? null,
                    'price_per_item' => $item['price_per_item'],
                ]);

                // Deduct fabric from inventory
                if (isset($item['fabric_id']) && isset($item['fabric_quantity_consumed'])) {
                    $fabric = Inventory::find($item['fabric_id']);
                    if ($fabric) {
                        $fabric->deductStock($item['fabric_quantity_consumed']);
                    }
                }
            }

            // Update customer totals
            $customer = Customer::find($request->customer_id);
            $customer->total_orders = $customer->orders()->count();
            $customer->total_spent = $customer->orders()->sum('total_amount');
            $customer->save();

            // Log activity
            ActivityLog::log(
                $request->user()->id,
                'create',
                'order',
                "Created order: {$order->order_number}",
                null,
                $order->toArray()
            );

            // Send WhatsApp notification if enabled
            $autoSend = \App\Models\SystemSetting::getValue('auto_whatsapp_order_created', true);
            if ($autoSend && $customer->phone) {
                $this->sendWhatsAppMessage($order, 'order_created');
            }

            DB::commit();

            // Load relationships
            $order->load(['customer', 'user', 'items']);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'trial_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,trial,completed,delivered,cancelled',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'advance_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $oldData = $order->toArray();

            // Update order fields
            $order->update($request->only([
                'trial_date', 'delivery_date', 'status', 'discount', 'tax', 'notes'
            ]));

            // Update advance payment if provided
            if ($request->has('advance_paid')) {
                $order->advance_paid = $request->advance_paid;
                $order->balance_amount = $order->total_amount - $order->advance_paid;
                $order->payment_status = $order->advance_paid >= $order->total_amount ? 'paid' : ($order->advance_paid > 0 ? 'partial' : 'pending');
                $order->save();
            }

            // Recalculate totals
            $order->calculateTotals();

            // Log activity
            ActivityLog::log(
                $request->user()->id,
                'update',
                'order',
                "Updated order: {$order->order_number}",
                $oldData,
                $order->toArray()
            );

            // Send WhatsApp notification on status change to completed
            if ($request->has('status') && $request->status === 'completed' && !$order->whatsapp_notification_sent) {
                $autoSend = \App\Models\SystemSetting::getValue('auto_whatsapp_order_completed', true);
                if ($autoSend && $order->customer->phone) {
                    $this->sendWhatsAppMessage($order, 'order_completed');
                    $order->whatsapp_notification_sent = true;
                    $order->save();
                }
            }

            // Send trial reminder
            if ($request->has('trial_date') && $request->trial_date) {
                $autoSend = \App\Models\SystemSetting::getValue('auto_whatsapp_trial_reminder', true);
                if ($autoSend && $order->customer->phone) {
                    $this->sendWhatsAppMessage($order, 'trial_reminder');
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order->load(['customer', 'items'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status only
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,in_progress,trial,completed,delivered,cancelled'
        ]);

        if (!$order->canTransitionTo($request->status)) {
            return response()->json([
                'message' => "Cannot transition from {$order->status} to {$request->status}"
            ], 422);
        }

        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'status_change',
            'order',
            "Order {$order->order_number} status changed from {$oldStatus} to {$request->status}",
            ['old_status' => $oldStatus],
            ['new_status' => $request->status]
        );

        // Send notifications based on status
        if ($request->status === 'completed' && !$order->whatsapp_notification_sent) {
            $autoSend = \App\Models\SystemSetting::getValue('auto_whatsapp_order_completed', true);
            if ($autoSend && $order->customer->phone) {
                $this->sendWhatsAppMessage($order, 'order_completed');
                $order->whatsapp_notification_sent = true;
                $order->save();
            }
        }

        if ($request->status === 'delivered') {
            $autoSend = \App\Models\SystemSetting::getValue('auto_whatsapp_delivery_thanks', true);
            if ($autoSend && $order->customer->phone) {
                $this->sendWhatsAppMessage($order, 'delivery_thanks');
            }
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order
        ]);
    }

    /**
     * Delete order
     */
    public function destroy(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        // Only allow deletion of pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be deleted'
            ], 422);
        }

        $oldData = $order->toArray();
        $orderNumber = $order->order_number;

        // Delete order items first (cascade will handle)
        $order->delete();

        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'delete',
            'order',
            "Deleted order: {$orderNumber}",
            $oldData,
            null
        );

        return response()->json([
            'message' => 'Order deleted successfully'
        ]);
    }

    /**
     * Generate invoice PDF
     */
    public function generateInvoice(Request $request, $id)
    {
        $order = Order::with(['customer', 'user', 'items'])->findOrFail($id);
        
        return $this->invoiceService->generate($order);
    }

    /**
     * Send WhatsApp message
     */
    public function sendWhatsApp(Request $request, $id)
    {
        $order = Order::with('customer')->findOrFail($id);
        
        $request->validate([
            'template_type' => 'required|in:order_created,trial_reminder,order_completed,delivery_thanks'
        ]);

        $result = $this->sendWhatsAppMessage($order, $request->template_type);

        return response()->json($result);
    }

    /**
     * Send WhatsApp message helper
     */
    private function sendWhatsAppMessage($order, $templateType)
    {
        try {
            $customer = $order->customer;
            
            // Get template based on language preference (default to 'en')
            $language = $customer->language_preference ?? 'en';
            $template = MessageTemplate::where('template_type', $templateType)
                                        ->where('language', $language)
                                        ->where('is_active', true)
                                        ->first();

            if (!$template) {
                $template = MessageTemplate::where('template_type', $templateType)
                                            ->where('language', 'en')
                                            ->first();
            }

            if (!$template) {
                return ['success' => false, 'message' => 'No template found'];
            }

            // Get shop settings
            $shopName = \App\Models\SystemSetting::getValue('shop_name', 'Tailoring Shop');
            $shopAddress = \App\Models\SystemSetting::getValue('shop_address', '');
            $shopHours = \App\Models\SystemSetting::getValue('shop_hours', '');

            // Prepare variables for template
            $variables = [
                'customer_name' => $customer->name,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date->format('d-m-Y'),
                'trial_date' => $order->trial_date ? $order->trial_date->format('d-m-Y') : 'Not scheduled',
                'delivery_date' => $order->delivery_date ? $order->delivery_date->format('d-m-Y') : 'Not scheduled',
                'total_amount' => number_format($order->total_amount, 2),
                'advance_paid' => number_format($order->advance_paid, 2),
                'balance' => number_format($order->balance_amount, 2),
                'shop_name' => $shopName,
                'shop_address' => $shopAddress,
                'shop_hours' => $shopHours,
            ];

            $message = $template->replaceVariables($variables);

            // For now, just log the message (integrate actual WhatsApp API later)
            $messageLog = MessageLog::create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'phone_number' => $customer->phone,
                'template_type' => $templateType,
                'message_content' => $message,
                'status' => 'pending',
                'sent_at' => null,
            ]);

            // Here you would integrate actual WhatsApp API
            // $this->whatsappService->send($customer->phone, $message);
            
            // For demo, mark as sent
            $messageLog->markAsSent('demo_message_id');

            return [
                'success' => true,
                'message' => 'WhatsApp message sent successfully',
                'log_id' => $messageLog->id
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'in_progress_orders' => Order::where('status', 'in_progress')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'today_deliveries' => Order::whereDate('delivery_date', today())->count(),
            'today_trials' => Order::whereDate('trial_date', today())->count(),
            'total_revenue' => Order::sum('total_amount'),
            'pending_payment' => Order::where('payment_status', 'pending')->sum('balance_amount'),
            'partial_payment' => Order::where('payment_status', 'partial')->sum('balance_amount'),
        ];

        return response()->json($stats);
    }
}