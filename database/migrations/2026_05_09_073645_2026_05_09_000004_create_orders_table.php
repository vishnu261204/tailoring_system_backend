<?php
// database/migrations/2026_05_09_000005_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->constrained();
            
            // Dates
            $table->date('order_date');
            $table->date('trial_date')->nullable();
            $table->date('delivery_date')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'in_progress', 'trial', 'completed', 'delivered', 'cancelled'])->default('pending');
            
            // Financials
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('tax', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('advance_paid', 12, 2)->default(0.00);
            $table->decimal('balance_amount', 12, 2)->default(0.00);
            
            // Payment status
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');
            
            // Additional info
            $table->text('notes')->nullable();
            $table->boolean('whatsapp_notification_sent')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('order_number');
            $table->index('customer_id');
            $table->index('status');
            $table->index('order_date');
            $table->index('delivery_date');
            $table->index('payment_status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};