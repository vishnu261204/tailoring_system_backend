<?php
// database/migrations/2026_05_09_000006_create_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            
            // Item Type
            $table->enum('item_type', ['shirt', 'pant', 'blouse', 'other']);
            $table->integer('quantity')->default(1);
            
            // Measurements
            $table->foreignId('measurement_id')->nullable()->constrained();
            $table->json('measurements_snapshot')->nullable();
            
            // Design & Stitching
            $table->string('stitch_type', 100)->nullable();
            $table->text('design_notes')->nullable();
            
            // Fabric details
            $table->foreignId('fabric_id')->nullable()->constrained('inventory');
            $table->string('fabric_name', 100)->nullable();
            $table->decimal('fabric_quantity_consumed', 10, 2)->nullable();
            
            // Pricing
            $table->decimal('price_per_item', 10, 2);
            $table->decimal('subtotal', 12, 2)->virtualAs('price_per_item * quantity');
            
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('item_type');
            $table->index('fabric_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};