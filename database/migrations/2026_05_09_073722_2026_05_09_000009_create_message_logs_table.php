<?php
// database/migrations/2026_05_09_000008_create_message_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('phone_number', 20);
            $table->string('template_type', 50)->nullable();
            $table->text('message_content')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'read'])->default('pending');
            $table->string('whatsapp_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('phone_number');
            $table->index('status');
            $table->index('sent_at');
            $table->index('template_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};