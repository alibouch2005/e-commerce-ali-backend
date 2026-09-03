<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_time_slot')->nullable()->after('delivery_longitude');
            $table->string('cancelled_by')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('minimum_amount')->constrained()->nullOnDelete();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_time_slot', 'cancelled_by', 'cancelled_at']);
        });
    }
};
