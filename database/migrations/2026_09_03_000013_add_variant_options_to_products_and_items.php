<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_variants')->default(false)->after('free_delivery');
            $table->json('variant_options')->nullable()->after('has_variants');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->json('selected_options')->nullable()->after('price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('selected_options')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('selected_options');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('selected_options');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['has_variants', 'variant_options']);
        });
    }
};
