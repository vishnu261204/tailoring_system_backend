<?php
// database/migrations/2026_05_09_000003_create_measurements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->integer('version')->default(1);
            $table->boolean('is_current')->default(true);
            
            // Shirt Measurements
            $table->decimal('shirt_chest', 5, 2)->nullable();
            $table->decimal('shirt_waist', 5, 2)->nullable();
            $table->decimal('shirt_shoulder', 5, 2)->nullable();
            $table->decimal('shirt_sleeve', 5, 2)->nullable();
            $table->decimal('shirt_collar', 5, 2)->nullable();
            $table->decimal('shirt_length', 5, 2)->nullable();
            
            // Pant Measurements
            $table->decimal('pant_waist', 5, 2)->nullable();
            $table->decimal('pant_hip', 5, 2)->nullable();
            $table->decimal('pant_length', 5, 2)->nullable();
            $table->decimal('pant_inseam', 5, 2)->nullable();
            $table->decimal('pant_thigh', 5, 2)->nullable();
            $table->decimal('pant_bottom', 5, 2)->nullable();
            
            // Blouse Measurements
            $table->decimal('blouse_chest', 5, 2)->nullable();
            $table->decimal('blouse_waist', 5, 2)->nullable();
            $table->decimal('blouse_shoulder', 5, 2)->nullable();
            $table->decimal('blouse_sleeve', 5, 2)->nullable();
            $table->decimal('blouse_length', 5, 2)->nullable();
            
            // Custom Measurements
            $table->json('custom_measurements')->nullable();
            
            $table->text('notes')->nullable();
            $table->foreignId('measured_by')->nullable()->constrained('users');
            $table->date('measurement_date');
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('is_current');
            $table->index('measurement_date');
            $table->unique(['customer_id', 'is_current'], 'uk_customer_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};