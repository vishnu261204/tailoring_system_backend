<?php
// app/Services/InvoiceService.php

namespace App\Services;

use App\Models\Order;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    protected $shopName;
    protected $shopAddress;
    protected $shopPhone;
    protected $shopEmail;
    protected $currencySymbol;
    protected $invoiceFooter;

    public function __construct()
    {
        $this->shopName = SystemSetting::getValue('shop_name', 'Tailoring Shop');
        $this->shopAddress = SystemSetting::getValue('shop_address', '');
        $this->shopPhone = SystemSetting::getValue('shop_phone', '');
        $this->shopEmail = SystemSetting::getValue('shop_email', '');
        $this->currencySymbol = SystemSetting::getValue('currency_symbol', '₹');
        $this->invoiceFooter = SystemSetting::getValue('invoice_footer', 'Thank you for your business!');
    }

    /**
     * Generate invoice PDF
     */
    public function generate(Order $order, $download = true)
    {
        // Load relationships
        $order->load(['customer', 'user', 'items']);

        // Prepare invoice data
        $data = $this->prepareInvoiceData($order);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', $data);

        // Configure PDF
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

        if ($download) {
            return $pdf->download("invoice_{$order->order_number}.pdf");
        }

        return $pdf;
    }

    /**
     * Stream invoice in browser
     */
    public function stream(Order $order)
    {
        $data = $this->prepareInvoiceData($order);
        $pdf = Pdf::loadView('pdf.invoice', $data);

        return $pdf->stream("invoice_{$order->order_number}.pdf");
    }

    /**
     * Save invoice to storage
     */
    // public function save(Order $order, $returnType = 'url')
    // {
    //     try {
    //         $data = $this->prepareInvoiceData($order);
    //         $pdf = Pdf::loadView('pdf.invoice', $data);

    //         $filename = "invoice_{$order->order_number}_{$order->id}.pdf";

    //         // Option 1: Save to storage/app/public/invoices (requires php artisan storage:link)
    //         $publicPath = storage_path('app/public/invoices');
    //         if (!file_exists($publicPath)) {
    //             mkdir($publicPath, 0755, true);
    //         }

    //         $fullPath = $publicPath . '/' . $filename;
    //         $pdf->save($fullPath);

    //         // Return based on requested type
    //         if ($returnType === 'url') {
    //             // Check if storage link exists
    //             if (is_dir(public_path('storage'))) {
    //                 return asset("storage/invoices/{$filename}");
    //             } else {
    //                 // Return direct file path
    //                 return $fullPath;
    //             }
    //         } elseif ($returnType === 'path') {
    //             return $fullPath;
    //         } elseif ($returnType === 'base64') {
    //             return base64_encode($pdf->output());
    //         }

    //         return $fullPath;
    //     } catch (\Exception $e) {
    //         \Log::error('Failed to save invoice: ' . $e->getMessage());  // Fixed here

    //         // Fallback: Return PDF as download instead of saving
    //         return $pdf->download($filename);  // Fixed here
    //     }
    // }

    /**
     * Get invoice URL with better error handling
     */
    // public function getInvoiceUrl(Order $order)
    // {
    //     $filename = "invoice_{$order->order_number}_{$order->id}.pdf";

    //     // Check different possible locations
    //     $possiblePaths = [
    //         public_path("storage/invoices/{$filename}"),
    //         storage_path("app/public/invoices/{$filename}"),
    //         storage_path("app/invoices/{$filename}")
    //     ];

    //     foreach ($possiblePaths as $path) {
    //         if (file_exists($path)) {
    //             if (strpos($path, 'public/storage') !== false) {
    //                 return asset("storage/invoices/{$filename}");
    //             } else {
    //                 return response()->download($path);
    //             }
    //         }
    //     }

    //     // If no file exists, generate new one
    //     return $this->save($order, 'url');
    // }

    /**
     * Prepare invoice data
     */
    private function prepareInvoiceData(Order $order)
    {
        // Calculate totals
        $subtotal = $order->subtotal;
        $discount = $order->discount;
        $tax = $order->tax;
        $total = $order->total_amount;
        $advancePaid = $order->advance_paid;
        $balanceDue = $order->balance_amount;

        // Get payment status label
        $paymentStatus = $this->getPaymentStatusLabel($order->payment_status);

        // Get order status label
        $orderStatus = $this->getOrderStatusLabel($order->status);

        return [
            'order' => $order,
            'customer' => $order->customer,
            'items' => $order->items,
            'user' => $order->user,
            'shop_name' => $this->shopName,
            'shop_address' => $this->shopAddress,
            'shop_phone' => $this->shopPhone,
            'shop_email' => $this->shopEmail,
            'currency_symbol' => $this->currencySymbol,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'advance_paid' => $advancePaid,
            'balance_due' => $balanceDue,
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'invoice_footer' => $this->invoiceFooter,
            'generated_date' => now()->format('d-m-Y H:i:s'),
        ];
    }

    /**
     * Get order status label
     */
    private function getOrderStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'trial' => 'Trial',
            'completed' => 'Completed',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get payment status label
     */
    private function getPaymentStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pending',
            'partial' => 'Partial Payment',
            'paid' => 'Paid'
        ];

        return $labels[$status] ?? ucfirst($status);
    }
}
