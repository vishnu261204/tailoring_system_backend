<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('meter', 8, 2)->nullable()->after('quantity');
            $table->string('fabric_image')->nullable()->after('meter');
            $table->decimal('price', 10, 2)->nullable()->after('fabric_image');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['meter', 'fabric_image', 'price']);
        });
    }
};
