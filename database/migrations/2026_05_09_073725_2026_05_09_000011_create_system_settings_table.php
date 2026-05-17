<?php
// database/migrations/2026_05_09_000010_create_system_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->enum('setting_type', ['text', 'json', 'boolean', 'number'])->default('text');
            $table->string('group_name', 50)->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('setting_key');
            $table->index('group_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};