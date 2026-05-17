<?php
// database/migrations/2026_05_09_000004_create_inventory_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('fabric_code', 50)->unique();
            $table->string('fabric_name', 100);
            $table->string('fabric_type', 50)->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('unit', 20)->default('meter');
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('cost_per_meter', 10, 2)->nullable();
            $table->decimal('selling_price_per_meter', 10, 2)->nullable();
            $table->string('supplier', 100)->nullable();
            $table->string('location', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('fabric_code');
            $table->index('fabric_name');
            $table->index('fabric_type');
            $table->index('quantity');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};