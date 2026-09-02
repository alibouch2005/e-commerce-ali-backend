<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->string('requested_product_city')->nullable()->after('requested_product_image');
            $table->index(['type', 'requested_product_city']);
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['type', 'requested_product_city']);
            $table->dropColumn('requested_product_city');
        });
    }
};
