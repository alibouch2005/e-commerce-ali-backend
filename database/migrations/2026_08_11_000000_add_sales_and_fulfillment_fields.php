<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('sale_price', 10, 2)->nullable()->after('price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_price');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('fulfillment_method', ['delivery', 'pickup'])->default('delivery')->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['sale_price', 'sale_ends_at']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('fulfillment_method'));
    }
};
