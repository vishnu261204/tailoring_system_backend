<?php
// database/migrations/2026_05_09_000007_create_message_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_code', 50)->unique();
            $table->enum('template_type', ['order_created', 'trial_reminder', 'order_completed', 'delivery_thanks']);
            $table->enum('language', ['en', 'ta'])->default('en');
            $table->string('subject', 200)->nullable();
            $table->text('body');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('template_type');
            $table->index('language');
            $table->index('is_active');
            $table->unique(['template_type', 'language'], 'uk_type_language');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};